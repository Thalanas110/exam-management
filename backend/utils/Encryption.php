<?php
include_once __DIR__ . '/../config/config.php';

class Encryption {
    private $method = 'aes-256-gcm';

    /**
     * Custom "Unique" Algorithm Wrapper
     * Reverses the string and applies a bitwise NOT to every byte.
     * This destroys the structure of the AES ciphertext even further.
     */
    private function customWrapper($data) {
        $reversed = strrev($data);
        $out = '';
        for ($i = 0; $i < strlen($reversed); $i++) {
            $out .= ~$reversed[$i];
        }
        return $out;
    }

    private function customUnwrapper($data) {
        $out = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $out .= ~$data[$i]; // NOT operator is its own inverse
        }
        return strrev($out);
    }

    public function encrypt($data) {
        // Step 1: AES-256-GCM
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $encrypted = openssl_encrypt($data, $this->method, ENCRYPTION_KEY, 0, $iv, $tag);
        
        // Combine for wrapping: IV . Tag . Ciphertext
        $combined = $iv . $tag . $encrypted;
        
        // Step 2: Custom Wrapper
        $wrapped = $this->customWrapper($combined);
        
        // Step 3: Encrypt the wrapped data AGAIN with AES (as per "Encrypt the result of step 2")
        // We need a new IV for this outer layer
        $outerIv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $outerEncrypted = openssl_encrypt($wrapped, $this->method, ENCRYPTION_KEY, 0, $outerIv, $outerTag);
        
        // Return format: Base64(OuterIV . OuterTag . OuterCiphertext)
        return base64_encode($outerIv . $outerTag . $outerEncrypted);
    }

    public function decrypt($data) {
        $raw = base64_decode($data);
        
        $ivLength = openssl_cipher_iv_length($this->method);
        $tagLength = 16; // GCM tag length is usually 16 bytes
        
        // Extract Outer Layer
        $outerIv = substr($raw, 0, $ivLength);
        $outerTag = substr($raw, $ivLength, $tagLength);
        $outerCipher = substr($raw, $ivLength + $tagLength);
        
        // Decrypt Outer Layer
        $wrapped = openssl_decrypt($outerCipher, $this->method, ENCRYPTION_KEY, 0, $outerIv, $outerTag);
        
        if ($wrapped === false) return false;
        
        // Unwrap Custom Layer
        $combined = $this->customUnwrapper($wrapped);
        
        // Extract Inner Layer
        $iv = substr($combined, 0, $ivLength);
        $tag = substr($combined, $ivLength, $tagLength);
        $encrypted = substr($combined, $ivLength + $tagLength);
        
        // Decrypt Inner Layer
        return openssl_decrypt($encrypted, $this->method, ENCRYPTION_KEY, 0, $iv, $tag);
    }
}
?>
