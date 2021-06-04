<?php
    namespace myJSON\Data\Encoders;

    class OpenSSLEncoder extends \myJSON\Data\Library\Base {
        private $cipher = "AES-256-CBC";
        private $key = 'ablelwldlwlapdklkdwia';
        private $vector = '12ablelkj539219x';
        public function __construct($cipher, $passphrase, $vector) {
            $this->cipher = $cipher;
            $this->passphrase = $passphrase;
            $this->vector = $vector;
        }
        public function getHash($value) {
            return base64_encode(openssl_encrypt($value, $this->cipher, $this->key, 0, $this->vector));
        }
        public function getValue($hash) {
            return openssl_decrypt(base64_decode($hash), $this->cipher, $this->key, 0, $this->vector);
        }
        public function getCipher() {
            return $this->cipher;
        }
    }
