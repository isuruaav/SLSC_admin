# sch_of_slsc architecture reference

The [Sinhala developer guide](GUIDE.si.md) and report generators in `tools/` are kept in Git. Generated HTML, Markdown reports, CSV/JSON inventories, diagrams and ZIP archives are local outputs excluded by `.gitignore`.

To regenerate the offline searchable report, run these commands from the project root after installing the development dependencies and configuring the local application:

```bash
php -d xdebug.mode=off docs/architecture/tools/extract_php.php
php -d xdebug.mode=off docs/architecture/tools/inspect_runtime.php
php -d xdebug.mode=off artisan route:list --json -vv > docs/architecture/routes-runtime.json
python docs/architecture/tools/build_data.py
python docs/architecture/tools/build_report.py
```

Then open the generated `docs/architecture/index.html` locally. Section 12 of the guide explains the requirements and limitations. Reports describe the local working tree; secrets and live application data are excluded.
