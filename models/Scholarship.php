<?php
class Scholarship {
    // Veritabanı bağlantısı ve tablo adı
    private $conn;
    private $table_name = "scholarships";

    // Nesne özellikleri
    public $id;
    public $name;
    public $amount;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Tüm bursları getir
    public function readAll() {
        $query = "SELECT id, name, amount FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Belirli bir bursu getir
    public function readOne() {
        $query = "SELECT id, name, amount FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->name = $row['name'];
            $this->amount = $row['amount'];
        }
    }

    // Yeni burs ekle
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name = :name, amount = :amount";
        $stmt = $this->conn->prepare($query);
        
        // Verileri temizle
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        
        // Parametreleri bağla
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":amount", $this->amount);
        
        return $stmt->execute();
    }

    // Burs güncelle
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET name = :name, amount = :amount WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Burs sil
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        return $stmt->execute();
    }
}
?>
