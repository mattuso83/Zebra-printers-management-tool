<script type="text/javascript">
$(document).ready(function() {
 $('#textbox1').val(this.checked);
    $('#checkbox1').change(function() {
        if(this.checked) {

        $.ajax({
            type: "post",
            url: "pfinderindustrial.php",
            data: $("form").serialize(),
            success: function(result) {
                $(".result").html(result);
            }
        });

    });
});
</script>