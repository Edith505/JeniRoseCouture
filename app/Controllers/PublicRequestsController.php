<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AttemptLimiter;
use App\Catalog\Catalogue;
use App\Database\Connection;
use App\Requests\ClientRequests;
use App\Requests\RequestInput;
use DomainException;
use RuntimeException;

final class PublicRequestsController
{
    public function dispatch(string $path, string $method): void
    {
        if (!in_array($method, ['GET', 'HEAD', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, HEAD, POST');
            return;
        }
        if ($method === 'HEAD') {
            return;
        }

        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        if (!session_start([
            'name' => 'jeni_requests',
            'use_strict_mode' => 1,
            'use_only_cookies' => 1,
            'cookie_httponly' => true,
            'cookie_secure' => !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off',
            'cookie_samesite' => 'Lax',
        ])) {
            throw new RuntimeException('Session de formulaire indisponible.');
        }

        if (isset($_SESSION['booking_review']) && $_SESSION['booking_review']['expires'] < time()) {
            unset($_SESSION['booking_review']);
        }

        $quote = $path === '/devis';
        $automatic = !$quote && ($_GET['mode'] ?? '') === 'automatic';
        $scope = $quote ? 'quote' : ($automatic ? 'automatic' : 'appointment');
        $formPath = '/index.php' . $path . ($automatic ? '?mode=automatic' : '');
        $database = Connection::open();
        $requests = new ClientRequests($database);
        $services = array_values(array_filter(Catalogue::published(), static fn (array $service): bool => $service['id'] !== null));
        $csrf = $_SESSION['tokens'][$scope] ??= bin2hex(random_bytes(32));
        $submission = $_SESSION['submission'][$scope] ??= bin2hex(random_bytes(32));
        $values = [];
        $message = null;
        $review = null;
        $selected = null;
        $selector = $_POST['service'] ?? $_GET['service'] ?? '';
        foreach ($services as $service) {
            if ((string) $service['id'] === $selector || $service['slug'] === $selector) {
                $selected = $service;
                break;
            }
        }

        $done = $method === 'GET' && isset($_SESSION['done'][$scope]);
        unset($_SESSION['done'][$scope]);
        if ($method === 'POST') {
            $provided = $_POST['csrf'] ?? null;
            if (!is_string($provided) || !hash_equals($csrf, $provided) || ($_POST['website'] ?? '') !== '') {
                http_response_code(403);
                $message = 'Le formulaire a expiré. Rechargez la page pour réessayer.';
            } else {
                $values = array_filter($_POST, 'is_string');
                try {
                    if ($automatic && ($_POST['intent'] ?? '') === 'confirm') {
                        $pending = $_SESSION['booking_review'] ?? null;
                        $reviewKey = $_POST['review_key'] ?? null;
                        if (!is_array($pending) || $pending['expires'] < time()
                            || !is_string($reviewKey) || !hash_equals($pending['key'], $reviewKey)
                        ) {
                            throw new DomainException('Le récapitulatif a expiré. Choisissez de nouveau un créneau.');
                        }

                        self::limit($database);
                        $requests->book($pending['service'], $pending['slot'], true, $pending['contact'], $pending['notes'], $pending['key']);
                    } else {
                        if ($selected === null) {
                            throw new DomainException('Choisissez une prestation.');
                        }
                        $contact = RequestInput::contact($_POST);
                        $notes = RequestInput::text($_POST, 'notes', 3000, $quote ? 'Projet' : 'Informations complémentaires', $quote);
                        $serviceId = (int) $selected['id'];

                        if ($automatic) {
                            $slot = RequestInput::id($_POST['slot'] ?? null);
                            $available = $slot === null ? null : ($requests->openings($selected, slotId: $slot)[0] ?? null);
                            if ($available === null) {
                                throw new DomainException('Choisissez un créneau encore disponible.');
                            }
                            $reviewKey = bin2hex(random_bytes(32));
                            $_SESSION['booking_review'] = [
                                'service' => $serviceId, 'slot' => $slot, 'contact' => $contact,
                                'notes' => $notes, 'key' => $reviewKey, 'expires' => time() + 900,
                            ];
                            $review = $available;
                        } else {
                            self::limit($database);
                            if ($quote) {
                                $channel = $_POST['channel'] ?? '';
                                if (!in_array($channel, ['email', 'phone'], true) || ($channel === 'phone' && $contact[2] === '')) {
                                    throw new DomainException('Choisissez un mode de contact avec les coordonnées correspondantes.');
                                }
                                $requests->submitQuote($serviceId, $contact, $channel, $notes, RequestInput::photos($_FILES['photos'] ?? null), $submission);
                            } else {
                                $requests->book($serviceId, null, false, $contact, $notes, $submission);
                            }
                        }
                    }

                    if ($review === null) {
                        if ($automatic) {
                            unset($_SESSION['booking_review']);
                        }
                        unset($_SESSION['tokens'][$scope], $_SESSION['submission'][$scope]);
                        $_SESSION['done'][$scope] = true;
                        header('Location: ' . $formPath, true, 303);
                        return;
                    }
                } catch (DomainException $error) {
                    if (http_response_code() !== 429) {
                        http_response_code(422);
                    }
                    $message = $error->getMessage();
                }
            }
        }

        if ($automatic && $method === 'GET' && ($_GET['edit'] ?? '') === '1') {
            $pending = $_SESSION['booking_review'] ?? null;
            if (is_array($pending) && $pending['expires'] >= time()) {
                [$values['name'], $values['email'], $values['phone'], $values['preference']] = $pending['contact'];
                $values['notes'] = $pending['notes'];
                $values['slot'] = (string) $pending['slot'];
            }
        }

        $slots = $automatic && $selected !== null && $review === null ? $requests->openings($selected) : [];
        $activePage = $path;
        $heading = $quote ? 'Demander un devis' : 'Prendre rendez-vous';
        $title = $heading . ' | Jeni Rose Couture';
        $intro = $quote ? 'Décrivez la pièce à créer, retoucher ou réparer.' : 'Choisissez une réservation en ligne ou envoyez une demande de rendez-vous.';
        $contentView = dirname(__DIR__) . '/Views/pages/request-form.php';
        require dirname(__DIR__) . '/Views/partials/public-page.php';
    }

    private static function limit(\PDO $database): void
    {
        if (!(new AttemptLimiter($database))->recordAttempt('demandes:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10)) {
            http_response_code(429);
            header('Retry-After: 900');
            throw new DomainException('Trop de demandes envoyées. Réessayez dans quelques minutes.');
        }
    }
}
