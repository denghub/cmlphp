<script type="text/javascript">
    (function(){
        console.log('%c cmlphp %c %s %o', 'background-color:rgb(255, 102, 0);color:white;padding:0 5px;','background-color:#62C462;', '<?php echo strip_tags($_SERVER['REQUEST_URI']); ?>', <?php echo json_encode($deBugLogData, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0);?>);
    })();
</script>