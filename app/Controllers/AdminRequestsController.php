<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminSession;
use App\Requests\ClientRequests;
use App\Requests\RequestInput;
use DomainException;

final class AdminRequestsController
{
    public function __construct(private readonly ClientRequests $requests)
    {
    }

    public function dispatch(string $method, int $actor, array $navigation): void
    {
        [$catalogueNavigation, $aboutNavigation, $requestNavigation, $salesNavigation] = $navigation;
        $requestNavigation = true;
        $activeAdminPage = 'requests';
        $csrf = AdminSession::token();
        $view = $_GET['view'] ?? 'appointments';
        if (!in_array($view, ['appointments', 'quotes', 'agenda', 'photo'], true)) {
            $this->missing($method);
            return;
        }

        $id = RequestInput::id($_GET['id'] ?? null);
        if ($view === 'photo') {
            if ($method === 'POST') {
                http_response_code(405);
                header('Allow: GET, HEAD');
                return;
            }
            $image = $id === null ? null : $this->requests->photo($id);
            if ($image === null) {
                $this->missing($method);
                return;
            }
            header('Content-Type: image/webp');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . strlen($image));
            if ($method === 'GET') {
                echo $image;
            }
            return;
        }

        $quote = $view === 'quotes';
        $agenda = $view === 'agenda';
        $base = '/index.php?route=admin-demandes&view=' . $view;
        $states = $quote ? ClientRequests::QUOTE_STATES : ClientRequests::APPOINTMENT_STATES;
        $record = $id === null || $agenda ? null
            : ($quote ? $this->requests->quote($id) : $this->requests->appointment($id));
        if (isset($_GET['id']) && $record === null) {
            $this->missing($method);
            return;
        }
        if ($method === 'HEAD') {
            return;
        }

        $error = null;
        if ($method === 'POST') {
            try {
                if ($agenda) {
                    if (($_POST['intent'] ?? '') === 'close') {
                        $slot = RequestInput::id($_POST['slot'] ?? null);
                        if ($slot === null) {
                            throw new DomainException('Créneau introuvable.');
                        }
                        $this->requests->closeOpening($slot);
                    } elseif (($_POST['intent'] ?? '') === 'close-day') {
                        $this->requests->closeDay(
                            RequestInput::text($_POST, 'day', 10, 'Date', true),
                            RequestInput::text($_POST, 'resource', 140, 'Personne ou équipe'),
                        );
                    } else {
                        $this->requests->addOpening($_POST);
                    }
                } elseif ($id !== null) {
                    $state = RequestInput::text($_POST, 'status', 15, 'Statut', true);
                    $notes = RequestInput::text($_POST, 'admin_notes', 3000, 'Notes internes');
                    if ($quote) {
                        $amount = str_replace(',', '.', RequestInput::text($_POST, 'amount', 12, 'Montant'));
                        if ($amount !== '' && !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $amount)) {
                            throw new DomainException('Indiquez un montant avec au maximum deux décimales.');
                        }
                        $reply = RequestInput::text($_POST, 'reply', 3000, 'Réponse');
                        $this->requests->updateQuote($id, $state, $amount === '' ? null : $amount, $reply, $notes);
                    } else {
                        $this->requests->updateAppointment($id, $state, RequestInput::id($_POST['slot'] ?? null), $notes, $actor);
                    }
                } else {
                    http_response_code(405);
                    header('Allow: GET, HEAD');
                    return;
                }
                header('Location: ' . $base . ($id === null ? '' : '&id=' . $id) . '&saved=1', true, 303);
                return;
            } catch (DomainException $exception) {
                http_response_code(422);
                $error = $exception->getMessage();
            }
        }

        $saved = ($_GET['saved'] ?? '') === '1';
        $filter = $_GET['status'] ?? '';
        $filter = is_string($filter) && isset($states[$filter]) ? $filter : '';
        $search = $_GET['search'] ?? '';
        $search = is_string($search) && mb_check_encoding($search, 'UTF-8') ? mb_substr(trim($search), 0, 100) : '';
        $heading = $agenda ? 'Créneaux et fermetures' : ($quote ? 'Demandes de devis' : 'Rendez-vous');
        $title = $heading . ' | Jeni Rose Couture';
        $slots = $history = $photos = $rows = [];
        if ($agenda) {
            $rows = $this->requests->agenda();
        } elseif ($record !== null) {
            if ($quote) {
                $photos = $this->requests->attachments($id);
            } else {
                $service = $this->requests->service((int) $record['service_id'], false);
                $slots = $service === null ? [] : $this->requests->openings($service, $id, true);
                $history = $this->requests->history($id);
            }
        } else {
            $rows = $quote ? $this->requests->quotes($filter, $search) : $this->requests->appointments($filter, $search);
        }

        $contentView = dirname(__DIR__) . '/Views/admin/requests.php';
        require dirname(__DIR__) . '/Views/partials/admin-shell.php';
    }

    private function missing(string $method): void
    {
        http_response_code(404);
        if ($method !== 'HEAD') {
            echo 'Demande introuvable.';
        }
    }
}
