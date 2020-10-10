<?php
    include_once 'utils/Query.php';
    class ServiceRecordsModel { //Make sure to use plural noun for the class name

        public function getServiceRecordColNames() {
            $query = new Query();

            // Call handler
            $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ServiceRecords'";
            $query->addToSql($sql);
            $resultContainer  = $query->handleQuery(); 
            return $resultContainer;
        } 


        public function updateAServiceRecord($start_time, $end_time,$id) {
            $query = new Query();
            $query->addToSql("UPDATE ServiceRecords SET start_time = ?");
            $query->addBindType("s");
            $query->addBindArrElem($start_time);
            if ($end_time != "") {
                $query->addToSql(", end_time = ?");
                $query->addBindType("s");
                $query->addBindArrElem($end_time);
            }
            $query->addToSql(" WHERE service_record_id = ?");
            $query->addBindType("i");
            $query->addBindArrElem($id);

            // $query->printSqlArr();
            // $query->printBindTypeArr();
            // $query->printBindArr();
    
            $query->handleQuery();
        }

        public function startService(int $trainer_id, int $pokemon_id, string $start_date = null) {
            // Declare vars
            $query = new Query();

            // if required fields not provided, throw exceptino
            try {
                if (!isset($trainer_id) || !isset($pokemon_id)) { // we can just infer date otherwise
                    throw new Exception("Error: insufficient arguments provided");  
                } 

                // user provided date? use that; otherwise take current timestamp
                $start_date = !isset($start_date) ? date('Y-m-d H:i:s') : $start_date; 

                    // find business state id for this time
                        // find most recent business state with a date less than start_date
                    $query_2 = new Query();
                    $query_2->setAll("SELECT bstate_id FROM BusinessStates
                                      WHERE date_changed <= ? ORDER BY date_changed DESC LIMIT 1;",
                                      "s", [$start_date]);
                    $resContainer2 = $query_2->handleQuery(); 

                    // if we can get the business state associated with the record, then continue
                    if ($resContainer2->isSuccess() && $resultContainer2->get_mysqli_result()->num_rows == 1) {
                        $bstate_id = $resContainer2->get_mysqli_result()->fetch_object()->bstate_id;
                        $sql = "INSERT INTO ServiceRecords(start_time, pokemon_id, trainer_id) 
                                VALUES (?,?,?,?);"; 
                        $bindArr = [$start_date, $pokemon_id, $trainer_id, $bstate_id];
                        $bindTypeStr = "siii";
                        $query->setAll($sql, $bindTypeStr, $bindArr);
                        $resultContainer  = $query->handleQuery(); 
                        return $resultContainer; 
                    }
                    // otherwise, do not proceed (application-level transaction)
                    else {
                        throw new Exception("Error: cannot associate service record with valid business state id");
                    }
            }
            catch (Exception $e) {
                return; // lower parts of code should be considered here (do later)
            }
        }

        public function endService(string $end_date=null, int $service_record_id) {
            try {
                // Declare vars
                $query = new Query();
                if (!isset($end_date)){
                    $end_date = date('Y-m-d H:i:s');
                }
                if (!isset($service_record_id)) {
                    throw new Exception("Error: insufficient arguments provided");  
                }
                $sql = "UPDATE ActiveServiceRecords 
                            SET end_time = ? WHERE service_record_id = ?;"; 
                $bindArr = [$end_date, $service_record_id];
                $bindTypeStr = "si";
                // $query->addToSql("UPDATE ActiveServiceRecords 
                //                  SET end_time = ? WHERE service_record_id = ?;");
                // $query->setBindArr([$end_date, $service_record_id]);
                // $query->setBindTypeStr("si");
                $query->setAll($sql, $bindTypeStr, $bindArr);

                // $resultContainer = $this->handleQuery($sql,$bindTypeStr,$bindArr); // returns ResultContainer
                $resultContainer = $query>handleQuery(); // returns ResultContainer
                return $resultContainer; // return type ResultContainer
            }
            catch (Exception $e) { // we will not return if this function is not called correctly
                // this should be logged because of the global logging settings set (thanks amon)
                return; // halt function 
            }
        }

        /* This is the parent method of several methods.
        $service_record_id : a unique service record id
        $trainer_id : a unique trainer id
        $pokemon_id : a unique pokemon id
        $date_range[2] : start date of a service record
            [$start_date, $end_date]
            If only one of the two is provided,
            that exact date will be used for an exact
            date search.
            If both provided, services records on the 
            range will be targeted (end dates included).
        $active_degree : degree active of service record
            0 : inactive
            1 : active
            2 : both (inactive and active)
            default : 2
        Always provide $active_degree if you do not want to target active records!
        Additionally, there are methods built on top of this method; these are 
        common operations. For anything more complex, use this method. 
        */
        public function getServiceRecords(int $service_record_id = null,
                                          int $trainer_id = null, 
                                          int $pokemon_id = null, 
                                          $date_range = null,
                                          $active_degree = 1) {

            $query = new Query();
            // Declare query assembling vars
            $s_where_conditions = [];
            
            $query->addToSql("SELECT service_record_id, start_time, end_time, pokemon_id,
            trainer_id FROM ");

            // Setting the table for correct active degree
            if ($active_degree == 0) { // inactive only
                $query->addToSql("InactiveServiceRecords ");
            }
            elseif ($active_degree == 1) { // active only
                $query->addToSql("ActiveServiceRecords ");
            }
            elseif ($active_degree == 2) { // both inactive and active
                $query->addToSql("ServiceRecords ");
                
            }
            else {
                // If time, define custom exception handler (this one is developer spec)
                throw new Exception("Invalid argument for '$active_degree'.");
            }

            if (isset($service_record_id) || isset($pokemon_id) || isset($trainer_id)) {
                $query->addToSql("WHERE ");    
            }
            // Start assembling query
            if (isset($service_record_id)) { // search by service_record_id
                $query->addToSql("service_record_id = ?");
                $query->addBindArrElem($service_record_id);
                $query->addBindType("i");
            }
            else {
                if (isset($pokemon_id)) {
                    $s_where_conditions[] = "pokemon_id = ?";
                    $query->addBindArrElem($pokemon_id);
                    $query->addBindType("i");
                }
                if (isset($trainer_id)) {
                    $s_where_conditions[] = "trainer_id = ?";
                    $query->addBindArrElem($trainer_id);
                    $query->addBindType("i");
                }         

                // Append conditions to base_sql
                $n_conditions = count($s_where_conditions)-1;
                if ($n_conditions >= 0) {
                    for($n = 0; $n < $n_conditions-1; $n++) { // add last condition with &&
                        // $base_sql = $base_sql.$condition." && ";
                        $query->addToSql($s_where_conditions[$n]." && ");
                    }
                    // Add last condition condition to base_sql
                    $query->addToSql($s_where_conditions[$n_conditions]);    
   
                }           
            }
            $query->addToSql(";");    
            $resultContainer = $query->handleQuery(); // returns ResultContainer
            return $resultContainer; // return type ResultContainer
        }

        public function getRatings() {

        }
           
        // COMMON GET BYS (DEFAULTS TO ALL STATUS, change as needed)
        public function getServiceRecordsByPokemonID($pokemon_id, $active_degree = 2) {
            return $this->getServiceRecords(null,null,$pokemon_id,null,$active_degree);
        }

        public function getServiceRecordsByTrainerID($trainer_id, $active_degree = 2) {
            return $this->getServiceRecords(null,$trainer_id,null,null,$active_degree);    
        }

        public function getServiceRecordByID($service_record_id, $active_degree = 2) {
            return $this->getServiceRecords($service_record_id,null,null,null,$active_degree);    
        }

        public function getServiceRecordsStartDate($start_date, $active_degree = 2) {
            return $this->getServiceRecords(null,null,null,$start_date,$active_degree);    
        }

        // GET ALLS
        public function getAllActiveServiceRecords() {
            return $this->getServiceRecords(null,null,null,null,1);
        }

        public function getAllInactiveServiceRecords() {
            return $this->getServiceRecords(null,null,null,null,0);
        }

        public function getAllServiceRecords() {
            return $this->getServiceRecords(null,null,null,null,2);
        }
    }
?>