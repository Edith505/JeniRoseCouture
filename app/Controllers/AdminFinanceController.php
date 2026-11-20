<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AdminSession;
use App\Dashboard\Period;
use App\Finance\Billing;
use App\Finance\FinanceInput;
use App\Finance\FinancePdf;
use App\Finance\Ledger;
use App\Requests\RequestInput;
use App\Sales\Sales;
use DomainException;

final class AdminFinanceController
{
    private const BASE = '/index.php?route=admin-finances';
    private const VIEWS = [
        'reports' => 'Suivi financier', 'payments' => 'Paiements', 'expenses' => 'Dépenses',
        'invoices' => 'Factures et avoirs', 'profile' => 'Profil de facturation',
    ];
    public const METRICS = [
        'invoiced_ht' => 'Facturé HT, net des avoirs',
        'invoiced_ttc' => 'Facturé TTC, net des avoirs',
        'received' => 'Encaissements',
        'refunds' => 'Remboursements',
        'expenses' => 'Dépenses TTC',
        'net_cash' => 'Solde net des mouvements',
        'outstanding' => 'Factures ouvertes à encaisser',
    ];

    public function __construct(
        private readonly Ledger $ledger,
        private readonly Billing $billing,
        private readonly Sales $sales,
    ) {
    }

    public function dispatch(string $method, int $actor, array $navigation): void
    {
        [$catalogueNavigation, $aboutNavigation, $requestNavigation, $salesNavigation, $financeNavigation] = $navigation;
        $activeAdminPage = 'finance';
        $csrf = AdminSession::token();
        $view = $_GET['view'] ?? 'reports';
        if (!is_string($view) || !isset(self::VIEWS[$view])) {
            http_response_code(404);
            return;
        }

        $id = RequestInput::id($_GET['id'] ?? null);
        $orderId = RequestInput::id($_GET['order'] ?? null);
        $base = self::BASE . '&view=' . $view . ($orderId === null ? '' : '&order=' . $orderId);
        $invoice = $view === 'invoices' && $id !== null ? $this->billing->document($id) : null;
        $order = $orderId === null ? null : $this->ledger->order($orderId);
        if ((isset($_GET['id']) && $invoice === null) || (isset($_GET['order']) && $order === null)) {
            http_response_code(404);
            return;
        }

        $error = null;
        $form = [];
        $key = $_SESSION['finance_key'] ??= bin2hex(random_bytes(32));
        if ($method === 'POST') {
            try {
                $intent = $_POST['intent'] ?? '';
                if ($view === 'profile' && $intent === 'profile') {
                    $this->billing->saveProfile(FinanceInput::issuer($_POST));
                } elseif ($view === 'payments' && $intent === 'payment' && $orderId !== null) {
                    $this->movementKey($key);
                    $day = FinanceInput::postedOn($_POST);
                    $direction = RequestInput::text($_POST, 'direction', 10, 'Type', true);
                    $methodName = RequestInput::text($_POST, 'method', 15, 'Mode', true);
                    $reference = RequestInput::text($_POST, 'reference', 140, 'Référence');
                    $this->ledger->payment($orderId, $day, $direction, $methodName, FinanceInput::amount($_POST), $reference, $actor, $key);
                    unset($_SESSION['finance_key']);
                } elseif ($view === 'expenses' && $intent === 'category') {
                    $this->ledger->addCategory(RequestInput::text($_POST, 'category_name', 140, 'Catégorie', true));
                } elseif ($view === 'expenses' && $intent === 'expense') {
                    $this->movementKey($key);
                    $category = RequestInput::id($_POST['category'] ?? null);
                    if ($category === null) {
                        throw new DomainException('Choisissez une catégorie.');
                    }
                    $description = RequestInput::text($_POST, 'description', 180, 'Description', true);
                    $this->ledger->expense($category, FinanceInput::postedOn($_POST), $description, FinanceInput::amount($_POST), $actor, $key);
                    unset($_SESSION['finance_key']);
                } elseif ($view === 'invoices' && $intent === 'invoice' && $orderId !== null) {
                    $id = $this->billing->issue($orderId, $_POST, $actor);
                    $base = self::BASE . '&view=invoices&id=' . $id;
                } elseif ($view === 'invoices' && $intent === 'credit' && $invoice !== null) {
                    $id = $this->billing->credit($id, RequestInput::text($_POST, 'reason', 450, 'Motif de l’avoir', true), $actor);
                    $base = self::BASE . '&view=invoices&id=' . $id;
                } else {
                    http_response_code(405);
                    header('Allow: GET, HEAD');
                    return;
                }
                header('Location: ' . $base . '&saved=1', true, 303);
                return;
            } catch (DomainException $exception) {
                $error = $exception->getMessage();
                $form = array_filter($_POST, 'is_string');
                http_response_code(422);
            }
        }

        if ($invoice !== null && ($_GET['format'] ?? '') === 'pdf' && $method !== 'POST') {
            $download = $this->billing->pdf($id);
            self::pdfResponse($download['number'] . '.pdf', $download['pdf'], $method);
            return;
        }
        if ($method === 'HEAD') {
            return;
        }

        try {
            $period = Period::fromQuery($_GET);
        } catch (DomainException $exception) {
            $error = $exception->getMessage();
            $period = Period::fromQuery([]);
            http_response_code(422);
        }
        $today = FinanceInput::today();
        $profile = ($view === 'profile' || $view === 'invoices') ? $this->billing->profile() : null;
        $orderChoices = ($view === 'payments' || $view === 'invoices') ? $this->ledger->orderChoices() : [];
        $rows = match ($view) {
            'payments' => $this->ledger->payments($period, $orderId),
            'expenses' => $this->ledger->expenses($period),
            'invoices' => $this->billing->list($period, $orderId),
            default => [],
        };
        $categories = $view === 'expenses' ? $this->ledger->categories() : [];
        $report = $view === 'reports' ? [
            'totals' => $this->ledger->totals($period),
            'categories' => $this->ledger->expensesByCategory($period),
            'labels' => self::METRICS,
            'from' => $period->start->format('d/m/Y'),
            'until' => $period->end->modify('-1 day')->format('d/m/Y'),
        ] : null;
        if ($report !== null && $error === null && ($_GET['format'] ?? '') === 'pdf') {
            self::pdfResponse('rapport-financier.pdf', FinancePdf::report($report), $method);
            return;
        }
        if ($report !== null && $error === null && ($_GET['format'] ?? '') === 'csv') {
            $this->csv($period, $report);
            return;
        }

        $financeActivity = $view === 'reports' ? $this->ledger->activity($period) : [];
        $activeInvoice = $order !== null && $view === 'invoices' ? $this->billing->activeInvoice((int) $order['id']) : null;
        $buyer = $order !== null && $view === 'invoices'
            ? $this->sales->client((int) $order['client_id']) : null;
        $title = self::VIEWS[$view] . ' | Jeni Rose Couture';
        $views = self::VIEWS;
        $saved = ($_GET['saved'] ?? '') === '1';
        $contentView = dirname(__DIR__) . '/Views/admin/finance.php';
        require dirname(__DIR__) . '/Views/partials/admin-shell.php';
    }

    private function movementKey(string $key): void
    {
        $given = $_POST['request_key'] ?? null;
        if (!is_string($given) || !hash_equals($key, $given)) {
            throw new DomainException('Le formulaire a déjà été utilisé. Vérifiez la liste avant de réessayer.');
        }
    }

    private static function pdfResponse(string $name, string $content, string $method): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($content));
        header('X-Content-Type-Options: nosniff');
        if ($method !== 'HEAD') {
            echo $content;
        }
    }

    private function csv(Period $period, array $report): void
    {
        $payments = $this->ledger->payments($period);
        $expenses = $this->ledger->expenses($period);
        $invoices = $this->billing->list($period);
        $safe = static fn (string $value): string => preg_match('/^[\p{Z}\s\x00-\x1F\x{FEFF}]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rapport-financier.csv"');
        header('X-Content-Type-Options: nosniff');
        echo "\xEF\xBB\xBF";
        $stream = fopen('php://output', 'wb');
        $write = static fn (array $row) => fputcsv($stream, $row, ';', '"', '');
        $write(['Registre', 'Date', 'Référence', 'Libellé', 'Montant EUR']);
        foreach ($report['labels'] as $key => $label) {
            $write(['Synthèse', '', '', $label, str_replace('.', ',', $report['totals'][$key])]);
        }
        foreach ($invoices as $row) {
            $write([$row['kind'] === 'credit' ? 'Avoir' : 'Facture', $row['issue_on'], $row['number'], $safe($row['name']),
                ($row['kind'] === 'credit' ? '-' : '') . str_replace('.', ',', $row['total_ttc'])]);
        }
        foreach ($payments as $row) {
            $write([$row['direction'] === 'refund' ? 'Remboursement' : 'Paiement', $row['booked_on'], 'Commande ' . $row['order_id'],
                self::csvLabel($safe, $row['reference'], $row['name']), ($row['direction'] === 'refund' ? '-' : '') . str_replace('.', ',', $row['amount'])]);
        }
        foreach ($expenses as $row) {
            $write(['Dépense', $row['booked_on'], '', $safe($row['category'] . ' · ' . $row['description']), str_replace('.', ',', $row['amount'])]);
        }
        fclose($stream);
    }

    private static function csvLabel(callable $safe, string $reference, string $name): string
    {
        return $safe($reference === '' ? $name : $name . ' · ' . $reference);
    }
}
