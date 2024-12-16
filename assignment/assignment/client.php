

<!DOCTYPE html>
<html>
<head>
<title>Currency Conversion</title>
<link rel="stylesheet" href="Currency_Styling.css">
</head>
<body>


  <h2>Restful API - CRUD: POST, PUT & DELETE Client Application</h2>
  <h4>*This version is not linked to the API service and is for demonstration purposes*</h4>
  <a href="https://kieranfarrer.co.uk/currencyapi/index.php?from=GBP&to=JPY&amnt=10.35&format=xml" target="_blank">"GBP" to "JPY" example API call as XML response</a>
  <br></br>

<div id = "ajax">
<h2> Choose an Action and select Currency to test:</h2>
	  <ul id="form-messages"></ul>
	<form action ="action.php" method = "post" class = "ajax">
	  <td><input class = "message_pri" name="action" value = "post" type = "radio" id = "method_post" >POST</td>
	  <td><input class = "message_pri" name="action" value = "put" type = "radio" id = "method_put" >PUT</td>
	  <td><input class = "message_pri" name="action" value ="del" type = "radio" id = "method_delete" >DELETE</td>

	  <select name = "currency" id = "currency">

      <option selected="selected" disabled> Select currency code </option>
      <?php
      include('codes.php');
      foreach($fixerCodes as $code){
        ?>
        <option value="<?php echo mb_strtoupper($code); ?>"><?php echo $code; ?></option>
      <?php
    }
    ?>
	  </select>
          <div id>
	        <input type ="submit" value = "send">
	</form>



  <h4>XML response via AJAX: </h4>

<textarea id="ajaxResponse" rows="20" cols="120" disabled style="border:none; ">

</textarea>


</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src = "main.js"> </script>


</body>
</html>