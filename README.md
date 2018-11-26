#Gebruik in combi met Google tag manager:
##Before all header scripts
<script>
	window.dataLayer = window.dataLayer || [];
</script>


##LVL 1 header scripts
<script>
	dataLayer.push({'event':'cookieconsent_functional'});
	dataLayer.push({'allowAdFeatures': 'false'});
	dataLayer.push({'anonimyzeIp': 'true'});
</script>

##LVL 2 header scripts
<script>
	dataLayer.push({'event':'cookieconsent_statistics'});
	dataLayer.push({'allowAdFeatures': 'false'});
	dataLayer.push({'anonimyzeIp': 'false'});
</script>

##LVL 3 header scripts
<script>
	dataLayer.push({'event':'cookieconsent_marketing'});
	dataLayer.push({'allowAdFeatures': 'true'});
	dataLayer.push({'anonimyzeIp': 'false'});
</script>


##After all header scripts
<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-xxxxx');</script>
<!-- End Google Tag Manager -->





#Gebruik in combi met Google analytics:
##Before all header scripts
<script type="text/javascript">
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
    ga('create', 'UA-xxxxxx-x');
    ga('send', 'pageview');
</script>

##LVL 1 header scripts
<script type="text/javascript">
    ga('set', 'anonymizeIp', true);
<!-- End Google Tag Manager -->

##LVL 2 header scripts
<script type="text/javascript">
    ga('set', 'anonymizeIp', false);
<!-- End Google Tag Manager -->
