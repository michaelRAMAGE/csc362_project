
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>


<script>
    // $(document).ready(function () {
    //     $('.updaters').hide(); //hide the submit button by default
    // });

    // function updateValue(element,col,id) {
    //     console.log("called");
    //     var selector = ("#").concat(col.concat(id));
    //     var new_val = element.innerText.concat("|",id).concat("|",col);
    //     $(selector).attr("value",new_val);
    //     if (new_val) {
    //         $('input#'.concat(id)).show();
    //     }
       
    // }
</script>


<?php
    include_once 'models/ServiceRecordsModel.php';
    include_once 'models/TrainersModel.php';
    include_once 'models/BusinessStatesModel.php';
    include_once 'models/PokemonModel.php';

    class ServiceRecordsView { //Make sure to use plural noun for the class name
        private $serviceRecordsModel;
        private $trainersModel;
        private $businsStatesModel;
        public function __construct() {
            //Make sure you don' put the $ sign in front of the variable name when using $this keyword!
            // e.g:   $this->trainersModel = new TrainersModel();
            $this->serviceRecordsModel = new ServiceRecordsModel();
            $this->trainersModel = new TrainersModel();
            $this->businessStatesModel = new BusinessStatesModel();
        }


        // CODE LOOKS UGLY, BUT IT BUILDS THE SERVICE RECORDS TABLE WITH EDITABLE DATE FIELDS 
        public function buildTableForm($action, $method, $row_headers, $table_data, $input_value) {
            $field_info = $table_data->get_mysqli_result()->fetch_fields(); 
            echo '
                <form id="services" action="'.$action.'" method="'.$method.'">
                    <table class="table">
                        <thead>
                            <th scope="col">RecordID</th>      
                            <th scope="col">Start Date</th>     
                            <th scope="col">End Date</th>  
                            <th scope="col">PokemonID</th>  
                            <th scope="col">TrainerID</th> 
                            <th scope="col">Save/Update</th> 
                            </tr>
                        </thead>
                        <tbody>';
                    //     <div contenteditable="true" onBlur=updateValue(this,"end_time","'.$id.'")> 
                    //     '.$row["end_time"].'
                    // </div>
                //     <input type="hidden" 
                //     id="end_time'.$id.'" 
                //     name="res[]" value="default"
                //     <input type="hidden" 
                //     id="start_time'.$id.'" 
                //     name="res[]" value="default"
                // >
                // <div contenteditable="true" onBlur=updateValue(this,"start_time"","'.$id.'")>
                //     '.$row["start_time"].'
                // </div>
                // >
            while ($row = $table_data->get_mysqli_result()->fetch_assoc()) { // for each row in table
                $id = $row["service_record_id"]; // unique service id

                $date_end = $row["end_time"];
                $date_strt = $row["start_time"];

                $temp_start_date = DateTime::createFromFormat('Y-m-d H:i:s', $date_strt);
                $temp_start_date_out = $temp_start_date->format('Y-m-d H:i:s');
                $start = str_replace(" ","T",$temp_start_date_out);

                $end;
                if ($date_end != NULL) {
                    $temp_end_date = DateTime::createFromFormat('Y-m-d H:i:s', $date_end);
                    $temp_end_date_out = $temp_end_date->format('Y-m-d H:i:s');
                    $end = str_replace(" ","T",$temp_end_date_out);
                }
                else {
                    $end = null;
                }
    
                echo '<tr> 
                        <td>'.$row["service_record_id"].'</td>
                        <input type="hidden" name="service_id[]" value="'.$id.'">
                        <td>
                            <input type="datetime-local"  name="start[]" value="'.$start.'" >
                        </td>
                        <td>
                            <input type="datetime-local"  name="end[]" value="'.$end.'" >
                        </td>
                       
                        </td>
                        <td>'.$row["pokemon_id"].'</td>
                        <td>'.$row["trainer_id"].'</td>
                        <td>
                            <div>
                                <input 
                                    class = "updaters"
                                    id="'.$id.'"
                                    type="submit"  
                                    visibility="hidden"
                                    name="'.$id.'"
                                > 
                            </div>
                        </td>
                    </tr>';
            }    
            echo 
            '       </tbody>
                </table>
            </form>'; 
        }

        public function buildServiceRecordsTable($action, string $by = null, $value = null, $status = null){
            //1. Get data from model
            $resultContainer; 
            $status = intval($status);
            if (!empty($value)) {
                $criteria = $by; 
                if (strpos($criteria, 'id') !== false) { $value = intval($value); }
                switch ($criteria) {
                    case "trainer_id":
                        $resultContainer = $this->serviceRecordsModel->getServiceRecordsByTrainerID($value, $status);
                        break;
                    case "service_record_id":
                        $resultContainer = $this->serviceRecordsModel->getServiceRecordByID($value, $status);
                        break;
                    case "pokemon_id":
                        $resultContainer = $this->serviceRecordsModel->getServiceRecordsByPokemonID($value, $status);
                        break;
                  }
            }
            else {
                switch ($status) {
                    case 0:
                        $resultContainer = $this->serviceRecordsModel->getAllInactiveServiceRecords();
                    break;
                    case 1:
                        $resultContainer = $this->serviceRecordsModel->getAllActiveServiceRecords();

                    break;
                    case 2:
                        $resultContainer = $this->serviceRecordsModel->getAllServiceRecords();
                    break; 
                }
            }

            if ($resultContainer->isSuccess()) { // will render based on what was set above
                // var_dump($resultContainer->get_mysqli_result());
                $this->buildTableForm("service-search.php","post", 
                    ["RecordID","Start Date", "End Date", "PokemonID", "TrainerID","Save/Update"],
                    $resultContainer,"service_record_id");
            }
            else { // do not render at all (maybe render some error, just depends)
                var_dump($resultContainer->getErrorMessages());
            } 
        }

        public function checkInConfirmationBox(int $trainer_id, int $pokemon_id, string $action, string $method, Array $form_params){
            //Renders the confirmation box to confirm the check in/out info and submit the request.
                 //      $action: The "action" value in HTML form. Determines where to send the form request.
                 //      $method: HTTP request to send this trainer_id and pokemon_id in the form.
                // $form_params: An array of (name, value) pairs of form parameters to send with the HTML form.
            $pokemonModel = new PokemonModel();
            $trainerModel = new TrainersModel();

            $pokemonReContainer = $pokemonModel->getPokemonByAttr($pokemon_id = $pokemon_id, $current_level = null, $upper_current_level = null, $nickname = null, $breedname = null, $active = false);
            $trainerReContainer = $trainerModel->getTrainerByAttr($trainer_id = $trainer_id, $email = null, $phone = null) ;
            if ($pokemonReContainer->isSuccess() && $trainerReContainer->isSuccess()){
                $pokemon_record = $pokemonReContainer->get_mysqli_result()->fetch_assoc();
                $pokemon_nickname = $pokemon_record? $pokemon_record["nickname"]: "Pokemon name not found";
                $pokemon_breed = $pokemon_record? $pokemon_record["breedname"]: "Unknown breed";

                $trainer_record = $trainerReContainer->get_mysqli_result()->fetch_assoc();
                $trainer_name = $trainer_record? $trainer_record["trainer_name"]: "No trainer name found";
                $invalid_request = ($pokemon_record==null || $trainer_record==null)? true: false;
                
                echo '
                <div class="jumbotron">
                    <form action="'.$action.'" method="'.$method.'">
                        <input type="hidden" name="trainer" value="'.$trainer_id.'">
                        <input type="hidden" name="trainer_name" value="'.$trainer_name.'">
                        <input type="hidden" name="pokemon" value="'.$pokemon_id.'">
                        <input type="hidden" name="pokemon_nickname" value="'.$pokemon_nickname.'">
                        ';
                        //Render hidden input based on $form_params
                        foreach ($form_params as $name=>$value){
                            echo '
                            <input type="hidden" name="'.$name.'" value="'.$value.'">
                            ';
                        };
                
                echo   '<h2 class="display-4">Check-In Confimration</h2>
                        <p class="lead">Please confirm the information below is correct.</p>
                        <hr class="my-4">
                        <p><b>Trainer</b>&nbsp;&nbsp;&nbsp;&nbsp;: '.$trainer_name.'</p>
                        <p><b>Pokemon</b>: '.$pokemon_nickname.' ('.$pokemon_breed.')</p>
                        <p class="lead" style="float:right;">
                            <a class="btn btn-info" href="select-pokemon.php?redirect-to=check-in-pokemon&active=false&trainer='.$trainer_id.'" role="button">Select other pokemon</a>
                            <button class="btn btn-info" type="submit" '.($invalid_request?"disabled":"").'>Check-in</button>
                        </p>
                    </form>
                </div>
                ';
            }
            $pokemonReContainer->mergeErrorMessages($trainerReContainer);
            return $pokemonReContainer;
        }
 
        public function checkInCompletionBox(int $trainer_id, string $trainer_name, $pokemon_nickname){
            //Renders comletion box for inserting a new service record (Check-in)
                //     int $trainer_id: The traier that has been checked-in
                //string $trainer_name: The traier that has been checked-in.
                //   $pokemon_nickname: The pokemon that has been checked-in.
            echo '
                <div class="jumbotron">
                    <h1 class="display-4">Check-In Complete!</h1>
                    <p class="lead">The check-in has been recorded. Go to Check-In/Out tab to check out the customer.</p>
                    <hr class="my-4">
                    <p class="lead" style="float:right;">
                        <a class="btn btn-info" href="select-pokemon.php?redirect-to=check-in-pokemon&active=false&trainer='.$trainer_id.'" role="button">Check-in '.$trainer_name.''."'".'s other Pok??mon</a>
                    </p>
                </div>';
        }

        public function checkOutCompletionBox(){
            //Renders comletion box for inserting a new service record (Check-in)
                //     int $trainer_id: The traier that has been checked-in
                //string $trainer_name: The traier that has been checked-in.
                //   $pokemon_nickname: The pokemon that has been checked-in.
            echo '
                <div class="jumbotron">
                    <h1 class="display-4">Check-Out Complete!</h1>
                    <p class="lead">The service record has been recorded.</p>
                    <hr class="my-4">
                    <p class="lead" style="float:right;">
                        <a class="btn btn-info" href="check-in-and-out.php?redirect-to=check-out-pokemon" role="button">Go back to check-out menu</a>
                    </p>
                </div>';
        }

        public function checkOutConfirmationBox(int $service_record_id, string $action, string $method, Array $form_params){
            //A box UI to submit the request to put end date to a particular service record on button click.
            $resultContainer = $this->serviceRecordsModel->getElaborateActiveServiceRecordById($service_record_id);
            $businesStateResult = $this->businessStatesModel->getCurrentBusinessState();
            if ($resultContainer->isSuccess() && $businesStateResult->isSuccess()){
                $service_record = $resultContainer->get_mysqli_result()->fetch_assoc();
                $invalid_request = $service_record? false: true;

                //Calculate the fee.
                $check_in_time = $service_record? strtotime($service_record["start_time"]): null;
                $check_out_time = time();
                $days = "?";
                $total_fee = "$---";
                if (!$invalid_request){
                    $now = time(); // or your date as well
                    $datediff = $now - $check_in_time;
                    $days = round($datediff / (60 * 60 * 24));
                    $rate = $businesStateResult->get_mysqli_result()->fetch_assoc()["price_per_day"];
                    $total_fee = $rate * $days;
                }

                $trainer_name = $service_record? $service_record["trainer_name"]: "No trainer found.";
                $pokemon_nickname = $service_record?  $service_record["nickname"]: "No Pokemon found.";
                $pokemon_breed = $service_record? $service_record["breedname"]: "Unknown species";
                echo '
                <div class="jumbotron">
                    <form action="'.$action.'" method="'.$method.'">
                    <input type="hidden" name="service" value="'.$service_record_id.'">
                        ';
                        //Render hidden input based on $form_params
                        foreach ($form_params as $key=>$value){
                            echo '
                            <input type="hidden" name="'.$key.'" value="'.$value.'">
                            ';
                        };
                
                echo   '<h3 class="display-5">Service Summary</h3>
                        <hr class="my-4">
                        <p><b>Trainer</b>&nbsp;&nbsp;&nbsp;&nbsp;: '.$trainer_name.'</p>
                        <p><b>Pokemon</b>: '.$pokemon_nickname.' ('.$pokemon_breed.')</p>
                        <p><b>Check-in  date</b>: '.date('m/d/Y', $check_in_time).'</p>
                        <p><b>Check-out date</b>: '.date('m/d/Y', $check_out_time).' ('.$days.' days of stay)</p>
                        <p><b>Rate per day</b>: $'.$rate.'</p>
                        <h3 class="display-8" style="float:right"><b> Total fee: $ '.$total_fee.'</b></h3>
                        <br><br><br>
                        <p class="lead" style="float:right;">
                            <a class="btn btn-info" href="check-in-and-out.php?redirect-to=check-out-pokemon" role="button">Cancel</a>
                            <button class="btn btn-info" type="submit" '.($invalid_request?"disabled":"").'>Check-Out and Finish</button>
                        </p>
                    </form>
                </div>
                ';
            }
            return $resultContainer;
        }

        public function serviceSelectionTableAll(string $action, string $method, Array $form_params){
            $resultContainer = $this->serviceRecordsModel->getElaborateActiveServiceRecords();
            if ($resultContainer->isSuccess()){
                echo '
                <form action="/submit" method="'.$method.'">';

                //Render hidden input based on $form_params
                foreach ($form_params as $key=>$value){
                    echo '
                    <input type="hidden" name="'.$key.'" value="'.$value.'">
                    ';
                };
                
                echo '
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Select</th>   
                                <th scope="col">Trainer</th>
                                <th scope="col">Pokemon</th>
                                <th scope="col">Check-in</th>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                while ($row = $resultContainer->get_mysqli_result()->fetch_assoc()) {
                    echo '  <tr>
                                <td>
                                    <div class="form-check">                                     
                                        <input class="form-check-input" type="radio" name="service" value="'.$row["service_record_id"].'" required>
                                    </div>
                                </td>
                                <td>'.$row["trainer_name"].'</td>
                                <td>'.$row["nickname"].'</td>
                                <td>'.$row["start_time"].'</td>
                            </tr>
                    ';
                }
                //Render the check-in/out buttons if there are search results.
                if ($resultContainer->get_mysqli_result()->num_rows!=0){
                    echo '  
                            <tr>
                                <td colspan="4"><button type="submit" value="'.$action.'" formaction="'.$action.'" style="float: right;margin-right:20px;" class="btn btn-info">Check-out</button></td>
                            </tr>
                    ';
                }
                //Render "not found" message if no records were found.
                if ($resultContainer->get_mysqli_result()->num_rows==0){
                    echo '
                            <tr>
                                <td colspan="12" width="100%" style="text-align: center;">No active service records found.</td>
                            </tr>
                    ';
                }
                echo '
                        </tbody>
                    </table>
                </form>';
        
            }
        }
    }
?>