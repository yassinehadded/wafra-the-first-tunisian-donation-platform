<?php
/**
 * User Model
 * Handles all user-related database operations
 */
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllUsers() {
        $sql = "SELECT cin, firstname, lastname, email, role, created_at, updated_at FROM users";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserByCin($cin) {
        $sql = "SELECT * FROM users WHERE cin = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addUser($cin, $firstname, $lastname, $email, $password, $role = 'user') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (cin, firstname, lastname, email, password, role, email_verified, verification_token, token_expiry)
                VALUES (:cin, :firstname, :lastname, :email, :password, :role, 0, NULL, NULL)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':cin' => $cin,
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':role' => $role
        ]);
    }

    public function updateUser($cin, $firstname, $lastname, $email, $role) {
        $sql = "UPDATE users SET firstname=?, lastname=?, email=?, role=? WHERE cin=?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$firstname, $lastname, $email, $role, $cin]);
    }

    public function deleteUser($cin) {
        $sql = "DELETE FROM users WHERE cin = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$cin]);
    }

    public function signIn($email, $password) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    private function generateToken() {
        return bin2hex(random_bytes(32));
    }

    public function generateVerificationToken() {
        return $this->generateToken();
    }

    public function sendVerificationEmail($userId, $email, $userName) {
        try {
            require_once __DIR__ . '/EmailUtility.php';
            $emailUtility = new EmailUtility();
            $token = $this->generateVerificationToken();

            error_log("User: Generating token for user ID: $userId, email: $email");

            $sql = "UPDATE users SET verification_token = :token, token_expiry = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE cin = :cin";
            $stmt = $this->pdo->prepare($sql);
            $tokenStored = $stmt->execute([
                ':token' => $token,
                ':cin' => $userId
            ]);
            
            if (!$tokenStored) {
                error_log("User: Failed to store verification token in database");
                return false;
            }

            error_log("User: Token stored successfully. Attempting to send email...");
            $emailSent = $emailUtility->sendVerificationEmail($email, $token, $userName);
            error_log("User: Email send result: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
            
            return $emailSent;
        } catch (Exception $e) {
            error_log("User: Exception in sendVerificationEmail: " . $e->getMessage());
            return false;
        }
    }

    public function createPasswordResetToken($email, $expiryMinutes = 30) {
        $user = $this->findUserByEmail($email);
        if (!$user) {
            return false;
        }
        $token = $this->generateToken();
        $sql = "UPDATE users SET reset_token = :token, reset_expires_at = DATE_ADD(NOW(), INTERVAL :minutes MINUTE) WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':minutes' => (int)$expiryMinutes,
            ':email' => $email
        ]);
        return ['token' => $token, 'user' => $user];
    }

    public function findUserByResetToken($token) {
        $sql = "SELECT * FROM users WHERE reset_token = :token AND reset_expires_at > NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function clearPasswordResetToken($email) {
        $sql = "UPDATE users SET reset_token = NULL, reset_expires_at = NULL WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
    }

    public function updatePassword($email, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = :password WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':password' => $hashed, ':email' => $email]);
    }

    public function updateUserProfile($cin, $firstname, $lastname, $email) {
        $sql = "UPDATE users SET firstname=?, lastname=?, email=? WHERE cin=?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$firstname, $lastname, $email, $cin]);
    }

    public function updateUserProfileWithPassword($cin, $firstname, $lastname, $email, $newPassword = null) {
        if ($newPassword !== null && trim($newPassword) !== '') {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET firstname=?, lastname=?, email=?, password=? WHERE cin=?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$firstname, $lastname, $email, $hashedPassword, $cin]);
        } else {
            return $this->updateUserProfile($cin, $firstname, $lastname, $email);
        }
    }

    public function updateProfilePicture($cin, $profilePicture) {
        try {
            $sql = "UPDATE users SET profile_picture = ? WHERE cin = ?";
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt) {
                error_log("Failed to prepare profile picture update statement. PDO Error: " . print_r($this->pdo->errorInfo(), true));
                return false;
            }
            $result = $stmt->execute([$profilePicture, $cin]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Failed to execute profile picture update. Error: " . print_r($errorInfo, true));
                return false;
            }
            error_log("Profile picture updated successfully for CIN: $cin, filename: $profilePicture");
            return true;
        } catch (PDOException $e) {
            error_log("PDOException updating profile picture for CIN: $cin. Error: " . $e->getMessage());
            if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'profile_picture') !== false) {
                error_log("ERROR: profile_picture column does not exist. Please run: ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL;");
            }
            return false;
        } catch (Exception $e) {
            error_log("Exception updating profile picture for CIN: $cin. Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendEmailChangeVerification($userId, $newEmail, $userName) {
        require_once __DIR__ . '/EmailUtility.php';
        $emailUtility = new EmailUtility();
        $token = $this->generateVerificationToken();

        $sql = "UPDATE users SET verification_token = :token, token_expiry = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE cin = :cin";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':cin' => $userId
        ]);

        return $emailUtility->sendEmailChangeVerification($newEmail, $token, $userName);
    }

    public function verifyEmail($token) {
        try {
            $sql = "SELECT * FROM users WHERE verification_token = :token AND token_expiry > NOW()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                error_log("User::verifyEmail - Found user with CIN: " . $user['cin'] . ", email: " . ($user['email'] ?? 'N/A'));
                
                $sql = "UPDATE users SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE cin = :cin";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([':cin' => $user['cin']]);
                
                if ($result) {
                    error_log("User::verifyEmail - Successfully updated email_verified for CIN: " . $user['cin']);
                    // Verify the update was successful
                    $checkSql = "SELECT email_verified FROM users WHERE cin = :cin";
                    $checkStmt = $this->pdo->prepare($checkSql);
                    $checkStmt->execute([':cin' => $user['cin']]);
                    $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    error_log("User::verifyEmail - Verification check - email_verified value: " . ($checkResult['email_verified'] ?? 'NULL'));
                } else {
                    error_log("User::verifyEmail - Failed to update email_verified for CIN: " . $user['cin']);
                }
                
                return $result;
            } else {
                error_log("User::verifyEmail - No user found with token or token expired");
            }

            return false;
        } catch (Exception $e) {
            error_log("User::verifyEmail - Exception: " . $e->getMessage());
            return false;
        }
    }

    public function isEmailVerified($cin) {
        $sql = "SELECT email_verified FROM users WHERE cin = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cin]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (bool)$result['email_verified'] : false;
    }

    public function findUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


