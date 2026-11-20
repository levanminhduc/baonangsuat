<?php

namespace App\Services;

use App\Support\Database;
use PDOException;

class DatchiPoService
{
    private $pdo;
    private $pdoLoaded = false;

    public function __construct($pdo = null)
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
            $this->pdoLoaded = true;
        }
    }

    private function pdo()
    {
        if (!$this->pdoLoaded) {
            $this->pdo = Database::getDatchi();
            $this->pdoLoaded = true;
        }
        return $this->pdo;
    }

    private function query($sql, array $params)
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return null;
        }
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('datchi: truy vấn thất bại (SQLSTATE ' . $e->getCode() . ')');
            return null;
        }
    }

    // status datchi trộn hoa/thường (default 'pending', enum 'CANCELLED') nên so bằng UPPER.
    private const PO_STYLE_FROM = '
        FROM po_items pi
        JOIN purchase_orders po ON po.id = pi.po_id
        JOIN styles st ON st.id = pi.style_id
        WHERE UPPER(TRIM(st.style_code)) = ?
          AND pi.deleted_at IS NULL AND po.deleted_at IS NULL AND st.deleted_at IS NULL
          AND UPPER(COALESCE(po.status::text, \'\')) <> \'CANCELLED\'';

    public function getPoListForStyle($styleCode)
    {
        $rows = $this->query(
            'SELECT po.po_number, po.customer_name, SUM(pi.quantity)::int AS quantity'
            . self::PO_STYLE_FROM
            . ' GROUP BY po.po_number, po.customer_name ORDER BY po.po_number DESC LIMIT 500',
            [strtoupper(trim($styleCode))],
        );
        if ($rows === null) {
            return ['available' => false, 'items' => []];
        }
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'po_number' => $row['po_number'],
                'customer_name' => $row['customer_name'],
                'quantity' => intval($row['quantity']),
            ];
        }
        return ['available' => true, 'items' => $items];
    }

    public function getPoQuantity($poNumber, $styleCode)
    {
        $rows = $this->query(
            'SELECT SUM(pi.quantity)::int AS quantity, COUNT(*) AS n'
            . self::PO_STYLE_FROM
            . ' AND po.po_number = ?',
            [strtoupper(trim($styleCode)), trim($poNumber)],
        );
        if ($rows === null) {
            return ['available' => false, 'quantity' => null];
        }
        $row = $rows[0] ?? null;
        $quantity = ($row && intval($row['n']) > 0) ? intval($row['quantity']) : null;
        return ['available' => true, 'quantity' => $quantity];
    }

    public function getSubArtsForStyle($styleCode)
    {
        $rows = $this->query(
            'SELECT sa.sub_art_code
             FROM sub_arts sa
             JOIN styles st ON st.id = sa.style_id
             WHERE UPPER(TRIM(st.style_code)) = ? AND st.deleted_at IS NULL
             ORDER BY sa.sub_art_code LIMIT 500',
            [strtoupper(trim($styleCode))],
        );
        if ($rows === null) {
            return ['available' => false, 'items' => []];
        }
        return ['available' => true, 'items' => array_column($rows, 'sub_art_code')];
    }
}
