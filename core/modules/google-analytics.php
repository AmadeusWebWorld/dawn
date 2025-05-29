<?php
if (!variable('google-analytics') || variable('local')) return;
if (variable('use-preview') && variable('live') === false) return;
?>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo variable("google-analytics"); ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
 	function gtag(){dataLayer.push(arguments);}
 	gtag('js', new Date());

 	gtag('config', '<?php echo variable("google-analytics"); ?>');
</script>
