<?php
    namespace myJSON\Providers;

    interface ProviderInterface {
        //get data provider
        public function getProvider();

        //execute query command
        public function query();

    }
 ?>
