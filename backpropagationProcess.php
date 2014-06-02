<?php

// include connect database

include 'config/config.php';

///////////////////////////////////////////////////
//MLP neural network in PHP
//Original source code by Phil Brierley
//www.philbrierley.com
//Translated into PHP - dspink Sep 2005
//This code may be freely used and modified at will
////////////////////////////////////////////////


//Tanh hidden neurons
//Linear output neuron

//To include an input bias create an
//extra input in the training data
//and set to 1


//////////////////////////////// User settings //////////////////
$numEpochs = 10000; 
$numHidden = 2;
$LR_IH = 0.2; //LEARNING RATE INPUT
$LR_HO = 0.2; //LEARNING RATE OUTPUT

//////////////////////////////// Data dependent settings //////////////////
$numInputs = 2;
// $numPatterns = 10;
$numPatterns;

////////////////////////////////////////////////////////////////////////////////

$maxUan;
$minUan;
$maxTest;
$minTest;

$patNum;
$errThisPat;
$outPred;
$RMSerror;

$trainInputs = array();
$trainOutput = array();


// the outputs of the hidden neurons

$hiddenVal = array();

// the weights
$weightsIH = array();
$weightsHO = array();

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Neural Network</title>
	</head>
	<body>
		
<?php

main();

if (isset($_POST['submit'])) {
	
	global $trainInputs;
	global $trainOutput;
	global $patNum;
	global $outPred;
	global $minUan;
	global $maxUan;
	global $minTest;
	global $maxTest;

	$trainInputs[0][0] = $_POST['uan'];
	$trainInputs[0][1] = $_POST['test'];

	$patNum = 0;

	dataNormalization();

	calcNet();

	echo "output ".$outPred;

}

//==============================================================
//********** THIS IS THE MAIN PROGRAM **************************
//==============================================================

function main() {

	global $numEpochs;
	global $numPatterns;
	global $patNum;
	global $RMSerror;

	// initiate the weights
	initWeights();

 	// load in the data
	initData();

 	// train the network
	for($j = 0;$j <= $numEpochs;$j++)
	{

		for($i = 0;$i<$numPatterns;$i++)
		{

			//select a pattern at random
			//srand();	
			$patNum = rand(0,$numPatterns-1);		 	   	

			//calculate the current network output
			//and error for this pattern
			calcNet();

			//change network weights
			WeightChangesHO();
			WeightChangesIH();
		}

		//display the overall network error
		//after each epoch
		calcOverallError();

		// echo "epoch = ".$j."  RMS Error = ".$RMSerror."</br>";

	}

	//training has finished
	//display the results
	displayResults();

 }

//============================================================
//********** END OF THE MAIN PROGRAM **************************
//=============================================================


//***********************************
function calcNet(){
	global $numHidden;
	global $hiddenVal;
	global $weightsIH;
	global $weightsHO;
	global $trainInputs;
	global $trainOutput;
	global $numInputs;
	global $patNum;
	global $errThisPat;
	global $outPred;


	//calculate the outputs of the hidden neurons
	//the hidden neurons are tanh

	for($i = 0;$i<$numHidden;$i++)
	{
		$hiddenVal[$i] = 0.0;

		for($j = 0;$j<$numInputs;$j++)
		{
			$hiddenVal[$i] = $hiddenVal[$i] + ($trainInputs[$patNum][$j] * $weightsIH[$j][$i]);
		}

		$hiddenVal[$i] = tanh($hiddenVal[$i]);

	}

 //calculate the output of the network
 //the output neuron is linear
	$outPred = 0.0;

	for($i = 0;$i<$numHidden;$i++)
	{
		$outPred = $outPred + $hiddenVal[$i] * $weightsHO[$i];
	}
		//calculate the error
		$errThisPat = $outPred - $trainOutput[$patNum];
}


//************************************
function WeightChangesHO(){
 //adjust the weights hidden-output
	global $numHidden;
	global $LR_HO;
	global $errThisPat; 
	global $hiddenVal;
	global $weightsHO;

	for($k = 0;$k<$numHidden;$k++)
	{
		$weightChange = $LR_HO * $errThisPat * $hiddenVal[$k];
		$weightsHO[$k] = $weightsHO[$k] - $weightChange;

		//regularisation on the output weights
		if ($weightsHO[$k] < -5)
		{
			$weightsHO[$k] = -5;
		}
		elseif ($weightsHO[$k] > 5)
		{
			$weightsHO[$k] = 5;
		}
	}
}


//************************************
function WeightChangesIH(){
 //adjust the weights input-hidden
	global $trainInputs;
	global $numHidden;
	global $numInputs;
	global $hiddenVal;
	global $weightsHO;
	global $weightsIH;
	global $LR_IH;
	global $patNum;
	global $errThisPat; 

	for($i = 0;$i<$numHidden;$i++)
	{
	 	for($k = 0;$k<$numInputs;$k++)
	 	{
			$x = 1 - ($hiddenVal[$i] * $hiddenVal[$i]);
			$x = $x * $weightsHO[$i] * $errThisPat * $LR_IH;
			$x = $x * $trainInputs[$patNum][$k];
			$weightChange = $x;
			$weightsIH[$k][$i] = $weightsIH[$k][$i] - $weightChange;
	 	}
	}
 }


//************************************
function initWeights(){
	global $numHidden;
	global $numInputs;
	global $weightsIH;
	global $weightsHO;

	for($j = 0;$j<$numHidden;$j++)
	{
		$weightsHO[$j] = (rand()/32767 - 0.5)/2;
		for($i = 0;$i<$numInputs;$i++)
		{
			$weightsIH[$i][$j] = (rand()/32767 - 0.5)/5;
		}
	}

}


//************************************
function initData(){
	global $trainInputs; 
	global $trainOutput;
	global $numPatterns;

	global $maxUan;
	global $minUan;
	global $maxTest;
	global $minTest; 
	
	$countData = 0;
	$tempUan = array();
	$tempTest = array();

	$query = mysql_query("SELECT * FROM train");

	while ($row = mysql_fetch_array($query)) {
		$uanData = $row['uan'];
		$testData = $row['test'];
		$targetData = $row['target'];

		$trainInputs[$countData][0] = $uanData;
		$trainInputs[$countData][1] = $testData;
		$trainOutput[$countData] = $targetData;

		$countData++;
	}

	$numPatterns = $countData;

	for ($i=0; $i < $countData ; $i++) 
	{ 
		array_push($tempUan, $trainInputs[$i][0]);
		array_push($tempTest, $trainInputs[$i][1]);
	}

	$maxUan = max($tempUan);
	$minUan = min($tempUan);
	$maxTest = max($tempTest);
	$minTest = min($tempTest);

	// normalization

	for ($i=0; $i < $countData ; $i++) { 
		$trainInputs[$i][0] = ($trainInputs[$i][0]-$minUan)/($maxUan-$minUan);
		$trainInputs[$i][1] = ($trainInputs[$i][1]-$minTest)/($maxTest-$minTest);
	}

	// manual input
		// $trainInputs[0][0]  = 0.55;
		// $trainInputs[0][1]  = 0.65;   //bias
		// $trainOutput[0] = 1;

		// $trainInputs[1][0]  = 0.89;
		// $trainInputs[1][1]  = 0.45;     //bias
		// $trainOutput[1] = 0;

		// $trainInputs[2][0]  = 0.78;
		// $trainInputs[2][1]  = 1;       //bias
		// $trainOutput[2] = 1;

		// $trainInputs[3][0]  = 1;
		// $trainInputs[3][1]  = 0.95;    //bias
		// $trainOutput[3] = 1;

		// $trainInputs[4][0]  = 0.11;
		// $trainInputs[4][1]  = 0.30;    //bias
		// $trainOutput[4] = 0;

		// $trainInputs[5][0]  = 0;
		// $trainInputs[5][1]  = 0;    //bias
		// $trainOutput[5] = 0;

		// $trainInputs[6][0]  = 0.78;
		// $trainInputs[6][1]  = 0.65;    //bias
		// $trainOutput[6] = 1;

		// $trainInputs[7][0]  = 0.67;
		// $trainInputs[7][1]  = 0.85;    //bias
		// $trainOutput[7] = 1;

		// $trainInputs[8][0]  = 0.33;
		// $trainInputs[8][1]  = 0.99;    //bias
		// $trainOutput[8] = 1;

		// $trainInputs[9][0]  = 0.11;
		// $trainInputs[9][1]  = 0.099;    //bias
		// $trainOutput[9] = 0;
	//

 }

//************************************

function dataNormalization(){
	global $maxUan;
	global $minUan;
	global $maxTest;
	global $minTest; 
	global $trainInputs;

	if ($trainInputs[0][0] < $minUan) { 
		$minUan = $trainInputs[0][0]; 
	} elseif ($trainInputs[0][0] > $maxUan) {
		$maxUan = $trainInputs[0][0];
	}

	if ($trainInputs[0][1] < $minTest) {
		$minTest = $trainInputs[0][1];
	} elseif ($trainInputs[0][1] > $maxTest) {
		$maxTest = $trainInputs[0][1];
	}

	$trainInputs[0][0] = ($trainInputs[0][0]-$minUan)/($maxUan-$minUan);
	$trainInputs[0][1] = ($trainInputs[0][1]-$minTest)/($maxTest-$minTest);


}



//************************************
function displayResults(){
	global $numPatterns;
	global $patNum;
	global $outPred;
	global $trainOutput;

	for($i = 0;$i<$numPatterns;$i++)
	{
		$patNum = $i;
		calcNet();
		echo "pat = ".($patNum+1)." actual = ".$trainOutput[$patNum]." neural model = ".$outPred."</br>";
	}
}


//************************************
function calcOverallError(){
	global $numPatterns;
	global $patNum;	
	global $errThisPat;
 	global $RMSerror;	

	$RMSerror = 0.0;
	for($i = 0;$i<$numPatterns;$i++)
	{
		$patNum = $i;
		calcNet();
		$RMSerror = $RMSerror + ($errThisPat * $errThisPat);
	}
	$RMSerror = $RMSerror/$numPatterns;
	$RMSerror = sqrt($RMSerror);
}


?>