<?php
/**
 * OTP Manager Class - Generates, stores, and verifies OTPs
 * Stores OTPs in database with expiration tracking
 */
require_once __DIR__ . '/db.php';

class OTPManager {
    private $otp_expiry_minutes = 10;
    
    /**
     * Generate a random 6-digit OTP
     */
    public function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get current time from database to ensure timezone consistency
     */
    private function getDatabaseNow(&$pdo) {
        try {
            $stmt = $pdo->query('SELECT NOW() as db_now');
            $result = $stmt->fetch();
            return $result['db_now'];
        } catch (Exception $e) {
            error_log("Error getting database time: " . $e->getMessage());
            return date('Y-m-d H:i:s');
        }
    }
    
    /**
     * Store OTP in database
     * Returns OTP on success, false on failure
     */
    public function storeOTP($target, $purpose = 'verification') {
        $pdo = getDB();
        if (!$pdo) return false;
        
        try {
            // Generate OTP
            $otp_code = $this->generateOTP();
            
            // Use database time to ensure timezone consistency
            $created_at = $this->getDatabaseNow($pdo);
            
            // Calculate expiry using database timestamps  
            // Add 10 minutes to the current database time
            $expires_at_sql = "DATE_ADD(NOW(), INTERVAL {$this->otp_expiry_minutes} MINUTE)";
            
            // Delete any existing unexpired OTPs for this target
            $del = $pdo->prepare('DELETE FROM ch_otps WHERE target = :target AND expires_at > NOW()');
            $del->execute([':target' => $target]);
            
            // Insert new OTP using NOW() for created_at and DATE_ADD(NOW()...) for expires_at for timezone consistency
            $ins_sql = "INSERT INTO ch_otps (target, otp_code, purpose, created_at, expires_at) 
                       VALUES (:target, :otp_code, :purpose, NOW(), DATE_ADD(NOW(), INTERVAL {$this->otp_expiry_minutes} MINUTE))";
            $ins = $pdo->prepare($ins_sql);
            $ins->execute([
                ':target' => $target,
                ':otp_code' => $otp_code,
                ':purpose' => $purpose,
            ]);
            
            return $otp_code;
        } catch (Exception $e) {
            error_log("OTP Store Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify OTP - returns true if valid, false otherwise
     */
    public function verifyOTP($target, $otp_code, $purpose = null) {
        $pdo = getDB();
        if (!$pdo) return false;
        
        try {
            // Trim whitespace from OTP code
            $otp_code = trim($otp_code);
            
            // First, get the OTP record to debug
            $debug_query = 'SELECT id, target, otp_code, purpose, created_at, expires_at FROM ch_otps WHERE target = :target ORDER BY created_at DESC LIMIT 1';
            $debug_stmt = $pdo->prepare($debug_query);
            $debug_stmt->execute([':target' => $target]);
            $debug_record = $debug_stmt->fetch();
            
            // Log verification attempt
            error_log("OTP Verify Attempt: target=$target, inputOTP=$otp_code, purpose=$purpose, dbRecord=" . json_encode($debug_record));
            
            // Now check if OTP exists and is valid
            $query = 'SELECT id FROM ch_otps WHERE target = :target AND otp_code = :otp_code AND expires_at > NOW()';
            $params = [':target' => $target, ':otp_code' => $otp_code];
            
            if ($purpose) {
                $query .= ' AND purpose = :purpose';
                $params[':purpose'] = $purpose;
            }
            
            $stmt = $pdo->prepare($query . ' LIMIT 1');
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                error_log("OTP Verify SUCCESS: target=$target, otp=$otp_code");
                // Delete the OTP after successful verification
                $del = $pdo->prepare('DELETE FROM ch_otps WHERE target = :target AND otp_code = :otp_code');
                $del->execute([':target' => $target, ':otp_code' => $otp_code]);
                return true;
            }
            
            error_log("OTP Verify FAILED: No matching OTP found or OTP expired. target=$target, inputOTP=$otp_code, purpose=$purpose");
            return false;
        } catch (Exception $e) {
            error_log("OTP Verify Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if there's an active OTP for a target
     */
    public function hasActiveOTP($target) {
        $pdo = getDB();
        if (!$pdo) return false;
        
        try {
            $stmt = $pdo->prepare('SELECT id FROM ch_otps WHERE target = :target AND expires_at > NOW() LIMIT 1');
            $stmt->execute([':target' => $target]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear expired OTPs (cleanup)
     */
    public function clearExpiredOTPs() {
        $pdo = getDB();
        if (!$pdo) return false;
        
        try {
            $del = $pdo->prepare('DELETE FROM ch_otps WHERE expires_at < NOW()');
            $del->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get OTP expiry time in minutes
     */
    public function getOTPExpiryMinutes() {
        return $this->otp_expiry_minutes;
    }
}
?>
