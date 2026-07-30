    </div>

    <script src="/painel_v4/public/assets/js/api.js?v=1"></script>
    <script src="/painel_v4/public/assets/js/config.js?v=1"></script>
    <script src="/painel_v4/public/assets/js/toast.js?v=1"></script>

<?php
if (!empty($pageScripts)) {
    foreach ($pageScripts as $script) {
        echo '    <script src="' . $script . '"></script>' . PHP_EOL;
    }
}
?>

</body>
</html>
