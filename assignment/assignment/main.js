$('form.ajax').on('submit',function() {


//this refernces the form
var that = $(this),
//grabs method and action from form
url = that.attr('action'),
type = that.attr('method'),
cur = that.attr('currency'),

//creating an object ready to store the data
data = {};

//gets the currency value selected in the drop down
that.find('[name = "currency"]').each(function(index,value){
var that = $(this),
name = that.attr('name');
value = that.val();
data[name]=value;
});

var message_pri = $(".message_pri:checked").val();

data["action"] = message_pri;


//for loop iterates through that variable
for( i = 0; i< that["0"].length ; i++)
{

//looking for what action button has been selected
  if (that["0"][i].checked == true){
//assigns the correct point to action variable
  var action = that["0"][i].value;
}
}


 $.ajax({
   url: url,
   type: type,
   data: {json:JSON.stringify(data)},
   success: function (response) {
   var get = response;
  document.getElementById("ajaxResponse").innerHTML = get;
}
});

return false;
});