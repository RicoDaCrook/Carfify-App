<?php
/**
 * Klasse zur Verwaltung von Fahrzeugverkäufen
 */
class Sale
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Erstellt einen neuen Verkaufsvorgang
     * @param array $data Verkaufsdaten
     * @return int ID des erstellten Verkaufs
     */
    public function createSale($data)
    {
        $query = "
            INSERT INTO sales (
                vehicle_id, user_id, estimated_price, final_price, 
                status, condition_data, created_at, updated_at
            ) VALUES (
                :vehicle_id, :user_id, :estimated_price, :final_price,
                :status, :condition_data, NOW(), NOW()
            ) RETURNING id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':vehicle_id' => $data['vehicle_id'],
            ':user_id' => $data['user_id'],
            ':estimated_price' => $data['estimated_price'],
            ':final_price' => $data['final_price'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':condition_data' => json_encode($data['condition_data'])
        ]);

        return $stmt->fetchColumn();
    }

    /**
     * Aktualisiert einen bestehenden Verkaufsvorgang
     * @param int $saleId ID des Verkaufs
     * @param array $data Zu aktualisierende Daten
     * @return bool Erfolg der Operation
     */
    public function updateSale($saleId, $data)
    {
        $query = "
            UPDATE sales SET
                final_price = COALESCE(:final_price, final_price),
                status = COALESCE(:status, status),
                condition_data = COALESCE(:condition_data, condition_data),
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':id' => $saleId,
            ':final_price' => $data['final_price'] ?? null,
            ':status' => $data['status'] ?? null,
            ':condition_data' => isset($data['condition_data']) ? json_encode($data['condition_data']) : null
        ]);
    }

    /**
     * Ruft Verkaufsdetails ab
     * @param int $saleId ID des Verkaufs
     * @return array Verkaufsdetails
     */
    public function getSale($saleId)
    {
        $query = "
            SELECT s.*, v.make, v.model, v.year, v.mileage
            FROM sales s
            JOIN vehicles v ON s.vehicle_id = v.id
            WHERE s.id = :id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $saleId]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale) {
            $sale['condition_data'] = json_decode($sale['condition_data'], true);
        }

        return $sale;
    }

    /**
     * Ruft alle Verkäufe eines Benutzers ab
     * @param int $userId ID des Benutzers
     * @return array Liste der Verkäufe
     */
    public function getUserSales($userId)
    {
        $query = "
            SELECT s.*, v.make, v.model, v.year, v.mileage
            FROM sales s
            JOIN vehicles v ON s.vehicle_id = v.id
            WHERE s.user_id = :user_id
            ORDER BY s.created_at DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sales as &$sale) {
            $sale['condition_data'] = json_decode($sale['condition_data'], true);
        }

        return $sales;
    }
}
