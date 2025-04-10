<?php
// डेटाबेस कनेक्शन कॉन्फिगरेशन
$host = '127.0.0.1';
$port = '8889';
$dbname = 'ankit';
$username = 'root';
$password = 'root';

try {
    // PDO इंस्टेंस बनाएं
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    
    // एरर मोड सेट करें
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // डिफॉल्ट फेच मोड सेट करें
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // एरर मैसेज दिखाएं
    die("Database connection failed: " . $e->getMessage());
}

// डेटाबेस हेल्पर क्लास बनाएं
class DB {
    private static $instance = null;
    private $connection;

    private function __construct($db) {
        $this->connection = $db;
    }

    public static function getInstance($db) {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    public function select($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function selectOne($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

// ग्लोबल DB इंस्टेंस बनाएं
$database = DB::getInstance($db);

function insert($table, $data) {
    global $db;
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($data);
    
    return $db->lastInsertId();
}

function delete($table, $where) {
    global $db;
    $whereClause = [];
    foreach ($where as $column => $value) {
        $whereClause[] = "$column = :$column";
    }
    $whereClause = implode(' AND ', $whereClause);
    
    $query = "DELETE FROM $table WHERE $whereClause";
    $stmt = $db->prepare($query);
    $stmt->execute($where);
    
    return $stmt->rowCount();
}

// हेल्पर फंक्शन्स
function select($query, $params = []) {
    global $db;
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function selectOne($query, $params = []) {
    global $db;
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch();
}
?> 