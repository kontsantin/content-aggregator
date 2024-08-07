<?php

require_once plugin_dir_path(__FILE__) . 'HTMLParser.php';
require_once plugin_dir_path(__FILE__) . 'export-functions.php';


?>
<style>
    .accordion {
        cursor: pointer;
        padding: 18px;
        width: 100%;
        text-align: left;
        border: none;
        outline: none;
        transition: 0.4s;
    }

    .accordion.active, .accordion:hover {
        background-color: #ccc;
    }

    .accordion + div {
        display: none;
        padding: 0 18px;
        background-color: white;
        overflow: hidden;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var acc = document.getElementsByClassName("accordion");
        for (var i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var panel = this.nextElementSibling;
                if (panel.style.display === "block") {
                    panel.style.display = "none";
                } else {
                    panel.style.display = "block";
                }
            });
        }
    });
</script>
