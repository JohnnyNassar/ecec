# Plugin patches

Files here are **patched copies** of vendor plugin files. The live plugin lives at `wp-content/plugins/<plugin>/...` but that tree is gitignored. After installing or updating the plugin, copy the patched file from here over the vendor file.

## emaurri-core/inc/core-dashboard/class-emaurricore-dashboard.php

`get_code()` patched to allow localhost **and** `207.180.196.39` so Emaurri Core shortcodes and CPTs register on both environments without a license check.

Apply:
```
cp patches/emaurri-core/inc/core-dashboard/class-emaurricore-dashboard.php \
   wp-content/plugins/emaurri-core/inc/core-dashboard/class-emaurricore-dashboard.php
```
