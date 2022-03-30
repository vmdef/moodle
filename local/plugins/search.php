<?php

require_once('../../config.php');
redirect(new local_plugins_url('/local/plugins/index.php', ['q' => optional_param('s', '', PARAM_TEXT)]));