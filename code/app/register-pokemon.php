<!DOCTYPE html>
<html lang="en">
<head>
    <?php   
            include_once 'utils/Settings.php';
            Settings::setup_debug(); //Custom function that contains debug settings
            include_once 'views/CommonView.php';
            include_once 'views/PokemonView.php';
            include_once 'views/InputErrorView.php';
            include_once 'controllers/PokemonContr.php';
    ?>
    <?php   
        $commonView = new CommonView();
        $commonView->header("Register Pokemon"); 
    ?>
</head>
<body>
    <?php 
        $commonView = new CommonView();
        $commonView->navbar();
    ?>
    <div style="margin-left:5%; margin-right:5%; margin-top: 25px;">
    <?php 
        $pokemonView = new PokemonView();
        $pokemonContr = new PokemonContr();
        $inputErrorView = new InputErrorView();
        $action = "register-pokemon.php";
        $method = "POST";
        $form_params = Array();

        if (isset($_GET["trainer"])){
            $trainer_id = $_GET["trainer"];
            if (isset($_GET["redirect_to"])){
                $form_params["redirect-to"] = $_GET["redirect-to"];
            }
            $pokemonView->pokemonRegistrationForm($trainer_id, $action, $method, $form_params);
        }else if (isset($_POST["trainer"])){
            $trainer_id = $_POST["trainer"];

            $form_params = Array();
            if (isset($_POST["redirect_to"])){
                $form_params["redirect-to"] = $_POST["redirect-to"];
            }

            //GET move names from POST
            $move_names = Array();
            if ($_POST["move-1"]!=""){
                $move_names[] = $_POST["move-1"];
            };
            if ($_POST["move-2"]!=""){
                $move_names[] = $_POST["move-2"];
            }
            if ($_POST["move-3"]!=""){
                $move_names[] = $_POST["move-3"];
            }
            if ($_POST["move-4"]!=""){
                $move_names[] = $_POST["move-4"];
            };

            $resultContPokemon = $pokemonContr->addPokemon($_POST["trainer"], $_POST["level"], $_POST["nickname"], $_POST["breedname"],
                                                            $move_names);
            if (!$resultContPokemon->isSuccess()){
                $errorMessages = $resultContPokemon->getErrorMessages();
                $inputErrorView->errorBox($errorMessages);
                $pokemonView->pokemonRegistrationForm($trainer_id, $action, $method, $form_params);
            }else{
                //On success, display success message
                $pokemonView->registrationSuccessBox($_POST["trainer"], $_POST["level"], $_POST["nickname"], $_POST["breedname"],
                $move_names);
            }
        }else{
            $inputErrorView->errorBox(Array("Error: Invalid request. Please start from the beginning."));
        }

    ?>
    </div>
</body>
</html>