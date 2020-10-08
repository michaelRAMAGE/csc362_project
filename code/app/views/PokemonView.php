<?php
    include 'models/PokemonModel.php';
    include 'views/MoveIndexView.php';

    class PokemonView { //Make sure to use plural noun for the class name
        private $pokemonModel;
        public function __construct() {
            $this->pokemonModel = new PokemonModel();
        }

        public function pokemonSelectionTableByTrainer(int $trainer_id,  bool $show_active, string $action, string $method, Array $form_params=Array()){
            // Renders a particular set of pokemon in table.
                //  $trainer_id: The ID of owner trainer of the pokemon you intend to get. 
                //      $action: The "action" value in HTML form. Determines where to send the form request.
                // $show_active: If set true, it will select the trainer's pokemon that are "active",
                //               meaning they are in daycare.
                // $form_params: An array of (name, value) pairs of form parameters to send with the HTML form.
            $resultContainer = $this->pokemonModel->getPokemonByTrainer($trainer_id, null, null, $show_active);
            $this->pokemonSelectionTable($resultContainer, $action, $method, $form_params);
        }

        public function pokemonSelectionTableAll(bool $show_active, string $action, string $method, Array $form_params=Array()){
            // Renders a particular set of pokemon in table. 
            // It renders all the pokemon that are either inactive or active.
                //      $action: The "action" value in HTML form. Determines where to send the form request.
                // $show_active: If set true, it will select the trainer's pokemon that are "active",
                //               meaning they are in daycare.
                // $form_params: An array of (name, value) pairs of form parameters to send with the HTML form.
            $resultContainer;
            if ($show_active){
                $resultContainer = $this->pokemonModel->getAllActivePokemon();
            }else{
                $resultContainer = $this->pokemonModel->getAllInactivePokemon();
            }
            $this->pokemonSelectionTable($resultContainer, $action, $method, $form_params);
        }

        private function pokemonSelectionTable(ResultContainer $resultContainer, string $action, string $method, Array $form_params){
            if ($resultContainer->isSuccess()){
                echo '
                <form action="'.$action.'" method="'.$method.'">';

                //Render hidden input based on $form_params
                foreach ($form_params as $name=>$value){
                    echo '
                    <input type="hidden" name="'.$name.'" value="'.$value.'">
                    ';
                };
                
                echo '
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Select</th>   
                                <th scope="col">Name</th>   
                                <th scope="col">Species</th>
                            </tr>
                        </thead>
                        <tbody>
                    ';
                while ($row = $resultContainer->get_mysqli_result()->fetch_assoc()) {
                    echo '  <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="pokemon" value="'.$row["pokemon_id"].'" required>
                                    </div>
                                </td>
                                <td>'.$row["nickname"].'</td>
                                <td>'.$row["breedname"].'</td>
                            </tr>
                    ';
                }
                if ($resultContainer->get_mysqli_result()->num_rows!=0){
                    echo '  
                            <tr>
                                <td colspan="3"><button type="submit" style="float: right;margin-right:20px;" class="btn btn-info">Select</button></td>
                            </tr>
                    ';
                }
                echo '
                        </tbody>
                    </table>
                </form>';
    
                //Render "not found" message if no records were found.
                if ($resultContainer->get_mysqli_result()->num_rows==0){
                    echo '
                            <p width="100%" style="text-align: center;">No matching pokemon found for the trainer.</p>
                    ';
                }

            }
            else{
                foreach ($resultContainer->getErrorMessages() as $errorMessage){
                    echo "<p>".$errorMessage."</p>";
                }
            }
        }

        public function pokemonRegistrationForm(int $trainer_id){
            //Renders a pokemon registration form

            echo '
            <div class="row justify-content-center">
            <div class="col-md-6">
            <div class="card">
            <header class="card-header">
                <h4 class="card-title mt-2">Register Pokémon</h4>
            </header>
            <article class="card-body">
            <form>
                <div class="form-group row">
                    <label for="nickname" class="col-3 col-form-label">Nick name</label>
                    <div class="col-9">
                    <input class="form-control" type="text" value="" id="nickname">
                    </div>
                    <label for="species" class="col-4 col-form-label"></label>
                    <small class="form-text text-muted">Nick name should be less than 17 characters.</small>
                </div>
                <div class="form-group row">
                    <label for="species" class="col-3 col-form-label">Species</label>
                    <div class="col-9">
                      <input class="form-control" type="text" value="" name="breedname" id="species">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="level" class="col-3 col-form-label">Level</label>
                    <div class="col-9">
                        <input class="form-control" type="number" name="level" value="1" min="1" max="100" id="level">
                    </div>
                    <label for="species" class="col-4 col-form-label"></label>
                    <small class="form-text text-muted">Level should be between 1-100.</small>
                </div>';
            //Render move dropdown boxes
            $MoveIndexView = new MoveIndexView();
            for($i=0;$i<4;$i++){
                echo '
                <div class="form-group row">
                    <label for="example-text-input" class="col-3 col-form-label">Move '.($i+1).'</label>
                    <div class="col-9">
                ';
                $MoveIndexView->moveDropdownBox("move", $i+1);
                echo '
                    </div>
                </div>
                ';
            }
            echo '
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block"> Register  </button>
                </div> <!-- form-group// -->      
            </form>
            </article> <!-- card-body end .// -->
            <div class="border-top card-body text-center"><a href="select-pokemon.php?active=false&redirect-to=check-in-confirmation&trainer='.$trainer_id.'">Go back and select from database</a></div>
            </div> <!-- card.// -->
            </div> <!-- col.//-->
            
            </div> <!-- row.//-->
            ';
        }
    }
?>