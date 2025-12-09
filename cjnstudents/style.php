<?php   
echo '

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script> 
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 

<script>
	function DragWindowLeft(Width,Height,Url)
		{
		Swal.fire({
		position:"top-end",showCloseButton: true, width:1200,
		  html:
		    \'<iframe  style="border: 1px none; z-index:2; width:\'+Width+\'vw; height:\'+Height+\'vw;  margin-left: -0px;margin-right: -0px;  margin-top: -0px; "  src="\'+Url+\'" ></iframe>\',
		  showConfirmButton: false,
		        })
		}

  $(\'#alert_gptcall\').click(function(e) {
		Swal.fire({
      position:"top-end",showCloseButton: true, width:1200,
        html:
          \'<iframe  style="border: 1px none; z-index:2; width:\'+Width+\'vw; height:\'+Height+\'vw;  margin-left: -0px;margin-right: -0px;  margin-top: -0px; "  src="\'+Url+\'" ></iframe>\',
        showConfirmButton: false,
              })
    });

</script> 

<style> 
.tooltip3 {
 position: relative;
  display: inline;
  border-bottom: 0px solid black;
font-size: 14px;
}

.tooltip3 .tooltiptext3 {
    
  visibility: hidden;
  width: 40%;
 
  background-color: #ffffff;
  color: #e1e2e6;
  text-align: center;
  font-size: 14px;
  border-radius: 10px;
  border-style: solid;
  border-color: #0aa1bf;
  padding: 20px 1;

  /* Position the tooltip */
  top:50;
  left:5%;
  position: fixed;
z-index: 1;
 
} 
.tooltip3 img {
  max-width: 600px;
  max-height: 1200px;
}
.tooltip3:hover .tooltiptext3 {
  visibility: visible;
}

 
</style>
';  
?>