<?php
/**
 * Login Session Model
 * Handles login session tracking
 */
class LoginSession {
    private $pdo;
    private $lastError = null;

    public function __construct($pdo) {
        if (!($pdo instanceof PDO)) {
            throw new InvalidArgumentException('LoginSession expects a valid PDO instance.');
        }
        $this->pdo = $pdo;
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function setLastError($message) {
        $this->lastError = $message;
        error_log('[LoginSession] ' . $message);
    }

    private function clearLastError() {
        $this->lastError = null;
    }

    public function createSession($userID, $username, $ipAddress = null, $device = null) {
        $this->clearLastError();

        if ($userID === null || $userID === '' || !is_numeric($userID) || (int)$userID <= 0) {
            $this->setLastError('User ID is missing or invalid when creating login session.');
            return false;
        }

        $sessionID = bin2hex(random_bytes(16));

        if ($ipAddress !== null && strlen($ipAddress) > 255) {
            $ipAddress = substr($ipAddress, 0, 255);
        }
        if ($device !== null && strlen($device) > 255) {
            $device = substr($device, 0, 255);
        }

        $sql = "INSERT INTO loginsession (SessionID, userID, ipAddress, device) VALUES (:sessionID, :userID, :ipAddress, :device)";
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt) {
            $errorInfo = $this->pdo->errorInfo();
            $this->setLastError("Failed to prepare createSession statement: {$errorInfo[0]} - {$errorInfo[1]}: {$errorInfo[2]}");
            return false;
        }
        try {
            $result = $stmt->execute([
                ':sessionID' => $sessionID,
                ':userID' => (int)$userID,
                ':ipAddress' => $ipAddress,
                ':device' => $device
            ]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->setLastError("createSession PDO execute error: {$errorInfo[0]} - {$errorInfo[1]}: {$errorInfo[2]}");
                return false;
            }
            return $sessionID;
        } catch (PDOException $e) {
            $this->setLastError('createSession PDOException: ' . $e->getMessage());
            error_log('createSession PDOException: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllSessions() {
        $sql = "SELECT 
                    ls.SessionID,
                    ls.userID,
                    ls.loginTime,
                    ls.logoutTime,
                    ls.ipAddress,
                    ls.device,
                    u.email AS userEmail,
                    u.firstname,
                    u.lastname,
                    CONCAT(u.firstname, ' ', u.lastname) AS username
                FROM loginsession ls
                LEFT JOIN users u ON u.cin = ls.userID
                ORDER BY ls.loginTime DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteSession($sessionID) {
        $sql = "DELETE FROM loginsession WHERE SessionID = :sessionID";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':sessionID' => $sessionID]);
    }

    public function updateLogoutTime($sessionID) {
        if (!$sessionID) {
            return false;
        }
        $sql = "UPDATE loginsession SET logoutTime = NOW() WHERE SessionID = :sessionID";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':sessionID' => $sessionID]);
    }
}


















