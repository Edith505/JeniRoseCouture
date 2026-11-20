<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminSession;
use App\Requests\RequestInput;
use App\Sales\OrderInput;
use App\Sales\Sales;
use DomainException;
use PDOException;

final class AdminSalesController
{
    public function __construct(private readonly Sales $sales)
    {
    }

    public function dispatch(string $route, string $method, int $actor, array $navigation): void
    {
        [$catalogueNavigation, $aboutNavigation, $requestNavigation, $salesNavigation, $financeNavigation] = $navigation;
        $salesNavigation = true;
        $clientPage = $route === 'admin-clients';
        $activeAdminPage = $clientPage ? 'clients' : 'orders';
        $base = '/index.php?route=' . $route;
        $id = RequestInput::id($_GET['id'] ?? null);
        $record = $id === null ? null : ($clientPage ? $this->sales->client($id) : $this->sales->order($id));
        if (isset($_GET['id']) && $record === null) {
            http_response_code(404);
            if ($method !== 'HEAD') {
                echo 'Fiche introuvable.';
            }
            return;
        }
        if ($method === 'HEAD') {
            return;
        }

        $create = ($_GET['new'] ?? '') === '1' && $id === null;
        $editing = $record !== null || $create;
        $error = null;
        $intent = $_POST['intent'] ?? 'save';
        $success = ($_GET['saved'] ?? '') === '1';
        $csrf = AdminSession::token();
        $formBase = $base . ($id !== null ? '&id=' . $id : '&new=1');
        $values = $record ?? [];
        $lines = $values['items'] ?? [[]];
        $activity = $history = $options = [];
        if (!$clientPage) {
            $values['deposit'] = $values['deposit_expected'] ?? '';
            if ($create && $method === 'GET') {
                $values['client_id'] = RequestInput::id($_GET['client'] ?? null);
            }
        }
        if ($method === 'POST') {
            try {
                if (!$clientPage && $intent === 'convert') {
                    if (!$requestNavigation) {
                        http_response_code(403);
                        echo 'Accès aux devis refusé.';
                        return;
                    }
                    $quote = RequestInput::id($_POST['quote_id'] ?? null);
                    if ($quote === null) {
                        throw new DomainException('Devis introuvable.');
                    }
                    self::redirect($base, $this->sales->convertQuote($quote, $actor));
                    return;
                }
                if (!$editing) {
                    http_response_code(405);
                    header('Allow: GET, HEAD');
                    return;
                }

                $values = array_replace($values, array_intersect_key(array_filter($_POST, 'is_string'), array_flip([
                    'name', 'email', 'phone', 'notes', 'address_line', 'postal_code', 'city', 'country',
                    'client_id', 'status', 'assignee', 'due_on', 'deposit',
                ])));
                if ($clientPage) {
                    if (($intent === 'archive' || $intent === 'restore') && $id !== null) {
                        $this->sales->archiveClient($id, $intent === 'archive');
                    } else {
                        [$name, $email, $phone] = RequestInput::contact($_POST);
                        $notes = RequestInput::text($_POST, 'notes', 3000, 'Notes internes');
                        $id = $this->sales->saveClient($id, [$name, strtolower($email), $phone, $notes], [
                            RequestInput::text($_POST, 'address_line', 180, 'Adresse'),
                            RequestInput::text($_POST, 'postal_code', 16, 'Code postal'),
                            RequestInput::text($_POST, 'city', 100, 'Ville'),
                            RequestInput::text($_POST, 'country', 80, 'Pays'),
                        ]);
                    }
                    self::redirect($base, $id);
                    return;
                }

                $lines = OrderInput::lines($_POST) ?: [[]];
                if (isset($_POST['remove_line'])) {
                    $position = filter_var($_POST['remove_line'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                    if ($position !== false && isset($lines[$position])) {
                        unset($lines[$position]);
                        $lines = array_values($lines) ?: [[]];
                    }
                } elseif ($intent === 'add-line' && count($lines) < 25) {
                    $lines[] = [];
                } elseif ($intent === 'save') {
                    self::redirect($base, $this->sales->saveOrder($id, OrderInput::data($_POST), $actor));
                    return;
                }
            } catch (DomainException | PDOException $exception) {
                if ($exception instanceof PDOException && ($exception->errorInfo[1] ?? null) !== 1062) {
                    throw $exception;
                }
                http_response_code($exception instanceof PDOException ? 409 : 422);
                $error = $exception instanceof PDOException ? 'Une fiche utilise déjà cette adresse e-mail.' : $exception->getMessage();
            }
        }

        $filter = $_GET['status'] ?? ($clientPage ? 'active' : '');
        $filter = is_string($filter) && in_array($filter, $clientPage ? ['active', 'all', 'archived'] : ['', ...array_keys(Sales::STATES)], true)
            ? $filter : ($clientPage ? 'active' : '');
        $search = $_GET['search'] ?? '';
        $search = is_string($search) && mb_check_encoding($search, 'UTF-8') ? mb_substr(trim($search), 0, 100) : '';
        $rows = $editing ? [] : ($clientPage ? $this->sales->clients($search, $filter) : $this->sales->orders($search, $filter));
        if ($editing) {
            if ($clientPage) {
                $activity = $id === null ? [] : $this->sales->clientActivity($id);
            } else {
                $options = $this->sales->orderOptions($record !== null ? (int) $record['client_id'] : null);
                $history = $id === null ? [] : $this->sales->history($id);
            }
        }

        $heading = $editing ? ($clientPage ? ($create ? 'Ajouter un client' : 'Fiche client') : ($create ? 'Créer une commande' : 'Commande n° ' . $id))
            : ($clientPage ? 'Clients' : 'Commandes');
        $title = $heading . ' | Jeni Rose Couture';
        $contentView = dirname(__DIR__) . '/Views/admin/sales.php';
        require dirname(__DIR__) . '/Views/partials/admin-shell.php';
    }

    private static function redirect(string $base, int $id): void
    {
        header('Location: ' . $base . '&id=' . $id . '&saved=1', true, 303);
    }
}
