"""Generate an offline, searchable HTML report and a full Markdown reference."""
from __future__ import annotations
import base64
import hashlib
import html
import json
import re
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
OUT = ROOT/'docs/architecture'
DATA = json.loads((OUT/'architecture-data.json').read_text(encoding='utf-8'))
FILES = {x['path']: x for x in DATA['files']}
ESC = html.escape
SERVICE_ROLES = {
    'AuditLogger':'Authentication සහ business actions සඳහා audit log records ලියයි.',
    'ContactMessageService':'Contact message read/resolve state වෙනස් කර audit කරයි.',
    'ContentSanitizer':'Shared rich text සහ plain-text values sanitize කරයි.',
    'DocumentCategoryService':'Document category create/update/active state rules.',
    'DocumentService':'Legacy document metadata, versions සහ workflow API; current simplified uploader වෙනම service එකක් භාවිතා කරයි.',
    'DocumentUploadManager':'Current bilingual document upload/version selection/save-and-publish flow.',
    'GalleryLocale':'Public gallery/news album URLs සහ language alternatives සකස් කරයි.',
    'GalleryService':'Gallery metadata, cover/image ordering සහ publication rules.',
    'LeadershipProfileContent':'Profile HTML/CSS sanitize කර Node/Tailwind compiler එකෙන් CSS සකස් කර sandbox document එක සාදයි.',
    'MediaDeletionService':'Media soft deletion, restore සහ guarded physical removal.',
    'MediaImageVariantService':'Uploaded images සඳහා derived image variants සහ cleanup.',
    'MediaMetadataService':'Media title/alt/caption වැනි metadata validate/update කරයි.',
    'MediaReplacementService':'Existing uploaded asset replace කිරීමේදී permissions, MIME/dimensions, file state සහ variants පාලනය කරයි.',
    'MediaUploadSecurity':'Upload MIME/extension/size/pixel allowlists config එකෙන් resolve කරයි.',
    'MediaUploadService':'UploadedFile validation -> physical storage -> MediaAsset/variants workflow.',
    'MediaUrlService':'Uploaded/external media සඳහා original/thumbnail/medium URLs සහ fallbacks.',
    'MenuService':'Menu/items/translations CRUD සහ nested ordering; public menus තවම මෙයට අදාළයි.',
    'NewsArticleService':'News article content create/update සහ associated validation.',
    'NewsCategoryService':'News category CRUD සහ active state.',
    'NewsDeletionService':'News soft delete/restore සහ related invariants.',
    'NewsImageService':'News image attachments සහ featured image selection.',
    'NewsRevisionService':'News revision snapshots සහ restoration.',
    'NewsWorkflowService':'News review/publish/unpublish transitions; retired archive entry point ද තවම ඇත.',
    'PageBlockRenderer':'Legacy structured page blocks safe display data බවට පරිවර්තනය කරයි.',
    'PageBlockSanitizer':'Structured page block payload normalize/validate කරයි.',
    'PageCss':'Custom page CSS validate කර page-specific selector scope යටතේ output කරයි.',
    'PageDeletionService':'Page delete/restore rules සහ related audit handling.',
    'PageHtmlSanitizer':'CMS HTML සහ visual-editor HTML වෙන වෙනම allowlists අනුව sanitize කරයි.',
    'PageRevisionService':'Page content/metadata/CSS revision capture සහ restore.',
    'PageWorkflowService':'Page review/publish/unpublish/return-to-draft transitions.',
    'PublicGalleryFeed':'Eligible manual galleries සහ news albums SQL pagination එකක එකතු කරයි.',
    'RedirectService':'Redirect destination validation සහ CRUD.',
    'SiteSettingService':'Site settings, localized fields සහ media relationships update කරයි.',
    'ThemeAssetPublisher':'Validated theme assets source -> public/themes copies publish කරයි.',
    'ThemeLayoutRenderer':'Published localized header/footer HTML/CSS sanitize කර known placeholders replace කරයි.',
    'ThemeLayoutSanitizer':'DB-managed layout/profile HTML සහ CSS rules sanitize/validate කරයි.',
    'ThemeLayoutService':'Header/footer drafts, publishing සහ revision records.',
    'ThemeManager':'Installed themes, safe slugs සහ theme.json manifests resolve කරයි.',
    'ThemeViewResolver':'Active theme එකේ requested view එක validated filesystem path එකකට resolve කරයි.',
}
CONTROLLER_ROLES = {
    'PublicHomeController':'Homepage data aggregate කර active theme homepage හෝ core fallback view render කරයි.',
    'PublicPageController':'Locale/slug/published date අනුව CMS page load කර sanitized HTML/blocks/SEO render කරයි.',
    'PublicNewsController':'Published localized news list/detail සහ theme fallback handling.',
    'PublicGalleryController':'Manual/news gallery feeds, detail pages සහ localized album routing.',
    'PublicDocumentController':'Current bilingual public document list/detail සහ safe view/download streams.',
    'PublicContactController':'Localized contact form display සහ throttled contact submission persistence.',
    'PublicSchoolLeadershipController':'Current/history/commandant/profile public leadership endpoints.',
    'PublicRedirectController':'Unmatched request සඳහා configured redirect lookup/fallback.',
    'PastCommandantController':'SchoolLeader commandant records වලින් history page සාදයි; theme view සහ currently missing core fallback reference එකක් ඇත.',
    'PastChiefInstructorController':'SchoolLeader chief_instructor records වලින් history page සහ locale handling.',
    'PastSergeantMajorController':'SchoolLeader warrant_officer appointment records වලින් sergeant major history සාදයි.',
    'DashboardController':'Admin dashboard statistics/data සකස් කර admin view render කරයි.',
    'PagePreviewController':'Authorized admin page preview; public rendering helper/sanitization සම්බන්ධ වේ.',
    'NewsPreviewController':'Authorized admin news preview data සහ rendering.',
    'Controller':'Shared Laravel controller base class.',
}
ROOT_ROLES = {
    '.env':'Private environment configuration; contents මේ report එකෙන් කියවා/export කර නැහැ.',
    '.env.example':'Environment variable template; actual production values සඳහා reference එකක් පමණි.',
    'artisan':'Console entry point -> Composer autoloader -> bootstrap/app.php.',
    'composer.json':'PHP requirements, PSR-4 autoload, development tools සහ setup/test/deploy scripts.',
    'composer.lock':'Locked PHP dependency versions; vendor tree reproducibility සඳහා අවශ්‍යයි.',
    'package.json':'Node build/runtime dependencies සහ Vite scripts.',
    'package-lock.json':'Locked Node dependencies සහ platform-specific optional packages.',
    'vite.config.js':'CSS/app/passkeys entry points, Tailwind plugin සහ font build configuration.',
    'phpunit.xml':'Pest/PHPUnit discovery සහ in-memory SQLite test environment.',
    'phpstan.neon':'Larastan/PHPStan level 7 source paths සහ temporary cache configuration.',
    'pint.json':'PHP code formatting configuration.',
    'boost.json':'Laravel Boost development/AI integration settings.',
    '.npmrc':'npm project settings; dependency installation behavior වෙනස් කළ හැකියි.',
    '.editorconfig':'Editor whitespace/encoding rules.',
    '.gitignore':'Version control තුළ generated files/secrets/dependencies exclude කරන patterns.',
    '.gitattributes':'Git text normalization සහ repository attributes.',
}
FOLDER_ROLES = {
    'app':'Application controllers, Livewire components, models සහ business services.',
    'bootstrap':'Application boot/provider registration සහ generated bootstrap caches.',
    'config':'Configuration definitions සහ environment bindings.',
    'database':'Migrations, seeders, factories සහ local database artifact.',
    'deploy':'Apache config සහ incremental release packages.',
    'lang':'Application translations.',
    'node_modules':'Node dependencies; profile compiler runtime dependency ද ඇතුළත්.',
    'public':'Web entry point, published/build assets සහ storage alias.',
    'resources':'Core Blade templates, frontend sources, fonts සහ safelist.',
    'routes':'Web/account/console route definitions.',
    'scripts':'Runtime profile compiler සහ dated release packaging tools.',
    'storage':'Application uploads/data සහ framework caches/logs.',
    'tests':'Local automated test coverage.',
    'themes':'Theme manifests, Blade templates, translations සහ asset sources.',
    'vendor':'Composer-installed PHP dependencies.',
    '.git':'Local Git history/metadata; source contents විශ්ලේෂණය කර නැහැ.',
    '.github':'CI workflow සහ dependency automation.',
    '.cursor':'Editor/AI integration configuration සහ local development skill files.',
    '.vscode':'VS Code workspace settings.',
}

def role(file):
    path=file['path']; stem=Path(path).stem
    if path in ROOT_ROLES: return ROOT_ROLES[path]
    if path.startswith('app/Services/'): return SERVICE_ROLES.get(stem,'Application business service.')
    if path.startswith('app/Http/Controllers/'): return CONTROLLER_ROLES.get(stem,'HTTP request handler.')
    if path.startswith('app/Models/'): return f'Eloquent model -> {file.get("table", "table resolved by convention")}; relationships, casts සහ query/model events මෙහි බලන්න.'
    if path.startswith('app/Livewire/'):
        module=path.split('/')[3] if '/Admin/' in path else 'account/settings'
        return f'{module} server-side Livewire component: state, UI actions, authorization සහ render/layout binding.'
    if path.startswith('app/Enums/'): return 'Allowed values/labels/transitions සඳහා PHP enum. Case values සහ consumers බලන්න.'
    if path.startswith('app/Providers/'): return 'Application boot integration: service registration, authentication/theme/view/authorization hooks.'
    if path.startswith('app/Http/Middleware/'): return 'Request pipeline guard; next handler වෙත යැවීම හෝ response/redirect තේරීම.'
    if path.startswith('app/Console/'): return 'Custom Artisan command. Signature සහ public handle() entry point බලන්න.'
    if path.startswith('app/Rules/'): return 'Laravel custom validation rule; related form/model/service validation සම්බන්ධ වේ.'
    if path.startswith(('app/Support/','app/Concerns/','app/Actions/')): return 'Shared helper/validation/action; callers සහ public methods පහත දැක්වේ.'
    if path.startswith('database/migrations/'): return 'Schema/data migration. Create/alter/down declarations Database catalog එකේ බලන්න; current live DB state බව නොසලකන්න.'
    if path.startswith('database/seeders/'): return 'Database bootstrap/data/permission seeder. Run කළ විට records වෙනස් කළ හැකියි.'
    if path.startswith('database/factories/'): return 'Automated tests/development සඳහා model fixture generation.'
    if path.startswith('tests/'):
        names=[x['description'] for x in file['tests']]
        return 'Automated regression coverage' + (': '+ '; '.join(names[:2]) if names else '; shared test setup/base class.')
    if path.endswith('.blade.php'):
        prefix='Theme Blade view' if path.startswith('themes/') else 'Core Blade view/component'
        suffix='; browser events server component methods වෙත යවයි' if file['wire_actions'] else ''
        return prefix+'; layout/includes/data producers සහ route links පහත දැක්වේ'+suffix+'.'
    if path.startswith('config/'): return 'Configuration namespace '+stem+'.*; reads කරන consumers සහ environment bindings බලන්න.'
    if path.startswith('routes/'): return 'Route registration entry; exact URI/name/handler/middleware Routes tab එකේ ඇත.'
    if path == 'scripts/compile-leadership-profile.mjs': return 'Runtime Node/Tailwind compiler. LeadershipProfileContent PHP Process එක stdin JSON යවයි; stdout CSS ආපසු ලැබේ.'
    if path.startswith('scripts/build-'): return 'Dated incremental deployment payload/hash/archive builder; website request handler එකක් නොවේ.'
    if path.startswith(('lang/','themes/')) and ('/lang/' in path or path.startswith('lang/')): return 'Localization resource; selected locale/namespace අනුව translation keys ලබාදෙයි.'
    if path.endswith('/theme.json'): return 'Theme manifest: slug, supported modules, default views සහ public asset source paths.'
    if path.endswith(('.css','.js','.mjs')): return 'Frontend styling/behavior source; imports, Blade consumers සහ build/published copies බලන්න.'
    if path.endswith(('.woff2','.woff','.ttf','.png','.ico','.svg')): return 'Font/image/icon asset; direct file references හා public/source consumers බලන්න.'
    if path.startswith('deploy/'): return 'Deployment/Apache configuration documentation; actual hosting state තහවුරු කළ යුතුයි.'
    if path.startswith(('.cursor/','.vscode/','.github/')): return 'Development/CI configuration; ordinary public request runtime එකට සාමාන්‍යයෙන් අවශ්‍ය නැහැ.'
    if path.startswith('bootstrap/'): return 'Framework bootstrap/provider registration file.'
    if path.startswith('public/'): return 'Web-facing entry/configuration/asset file.'
    return 'Project resource/configuration. Detected references සහ parent folder responsibility බලන්න.'

for file in DATA['files']:
    file['role']=role(file)
    file['group']=file['path'].split('/')[0] if '/' in file['path'] else 'Root files'
DATA['folder_roles']=FOLDER_ROLES

def inline(text):
    tokens=[]
    def code(match):
        value=match[1]; label='<code>'+ESC(value)+'</code>'
        if value in FILES: label='<a href="#file='+ESC(value,quote=True)+'" data-file="'+ESC(value,quote=True)+'">'+label+'</a>'
        tokens.append(label); return f'\x00{len(tokens)-1}\x00'
    text=re.sub(r'`([^`]+)`',code,text)
    text=ESC(text)
    text=re.sub(r'\*\*([^*]+)\*\*',r'<strong>\1</strong>',text)
    text=re.sub(r'\[([^\]]+)\]\(([^)]+)\)',r'<a href="\2">\1</a>',text)
    for i,value in enumerate(tokens): text=text.replace(f'\x00{i}\x00',value)
    return text

def markdown_html(markdown):
    lines=markdown.splitlines(); output=[]; headings=[]; i=0
    while i<len(lines):
        line=lines[i]
        if not line.strip(): i+=1; continue
        if line.startswith('```'):
            language=line[3:]; block=[]; i+=1
            while i<len(lines) and not lines[i].startswith('```'): block.append(lines[i]); i+=1
            output.append('<pre><code data-language="'+ESC(language)+'">'+ESC('\n'.join(block))+'</code></pre>'); i+=1; continue
        match=re.match(r'^(#{1,6})\s+(.*)',line)
        if match:
            level=len(match[1]); ident='guide-'+str(len(headings)+1); headings.append((level,ident,match[2]))
            output.append(f'<h{level} id="{ident}">'+inline(match[2])+f'</h{level}>'); i+=1; continue
        if line.startswith('|') and i+1<len(lines) and re.match(r'^\|[\s:|\-]+\|$',lines[i+1]):
            heads=[x.strip() for x in line.strip('|').split('|')]; i+=2; rows=[]
            while i<len(lines) and lines[i].startswith('|'): rows.append([x.strip() for x in lines[i].strip('|').split('|')]); i+=1
            output.append('<div class="table-wrap"><table><thead><tr>'+''.join('<th>'+inline(x)+'</th>' for x in heads)+'</tr></thead><tbody>'+''.join('<tr>'+''.join('<td>'+inline(x)+'</td>' for x in row)+'</tr>' for row in rows)+'</tbody></table></div>'); continue
        if line.startswith('- '):
            items=[]
            while i<len(lines) and lines[i].startswith('- '): items.append(lines[i][2:]); i+=1
            output.append('<ul>'+''.join('<li>'+inline(x)+'</li>' for x in items)+'</ul>'); continue
        paragraph=[line]; i+=1
        while i<len(lines) and lines[i].strip() and not lines[i].startswith(('#','```','|','- ')): paragraph.append(lines[i]); i+=1
        output.append('<p>'+inline(' '.join(paragraph))+'</p>')
    return '\n'.join(output),headings

guide=(OUT/'GUIDE.si.md').read_text(encoding='utf-8')
for key,value in DATA['counts'].items(): guide=guide.replace('{{'+key+'}}',f'{value:,}')
guide_html,headings=markdown_html(guide)
font=base64.b64encode((ROOT/'themes/school-of-signals/assets/fonts/NotoSerifSinhala-Regular.woff2').read_bytes()).decode()

SVG='''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 390" role="img" aria-labelledby="title desc"><title id="title">sch_of_slsc application request flow</title><desc id="desc">Browser to public entry, bootstrap, middleware, routes, controllers and Livewire, services, models, storage, and Blade views.</desc><defs><marker id="arrow" markerWidth="10" markerHeight="10" refX="9" refY="5" orient="auto"><path d="M0 0L10 5L0 10" fill="#61748e"/></marker></defs><rect width="1080" height="390" rx="18" fill="#f1f5fb"/><g font-family="Segoe UI,Arial,sans-serif" font-size="15" text-anchor="middle">'''
boxes=[(20,30,165,62,'Browser / Apache','public/.htaccess'),(220,30,165,62,'public/index.php','Composer autoload'),(420,30,185,62,'bootstrap/app.php','providers + middleware'),(640,30,185,62,'routes/web.php','routes/settings.php'),(870,30,190,62,'Controller / Livewire','request + authorization'),(870,154,190,62,'Services','rules + sanitization'),(640,154,185,62,'Models / Eloquent','database tables'),(420,154,185,62,'Storage / media','public + private files'),(220,276,385,70,'Blade + theme layouts + translations','core views / active theme / published layouts'),(640,276,420,70,'Browser assets / response','public/build + public/themes + public/storage')]
for x,y,w,h,a,b in boxes:
    SVG+=f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="10" fill="white" stroke="#cbd5e1"/><text x="{x+w/2}" y="{y+25}" fill="#112c51" font-weight="700">{ESC(a)}</text><text x="{x+w/2}" y="{y+46}" fill="#52657e" font-size="12">{ESC(b)}</text>'
SVG+='</g><g stroke="#61748e" stroke-width="2" fill="none" marker-end="url(#arrow)"><path d="M185 61H216"/><path d="M385 61H416"/><path d="M605 61H636"/><path d="M825 61H866"/><path d="M965 92V150"/><path d="M870 185H829"/><path d="M965 216V237H512V220"/><path d="M965 216V255H413V272"/><path d="M605 311H636"/></g></svg>'
(OUT/'request-flow.svg').write_text(SVG,encoding='utf-8')

CSS=r'''
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;color:#192b43;background:#edf2f8;font-family:Segoe UI,Arial,DocSinhala,sans-serif;font-size:14px;line-height:1.8}button,input,select{font:inherit}button,a{touch-action:manipulation}button{cursor:pointer}a{color:#1260a6;text-decoration:none}a:hover{text-decoration:underline}code{font:12px/1.7 Consolas,monospace;background:#eef3f9;padding:2px 5px;border-radius:4px;overflow-wrap:anywhere}pre{padding:18px;border-radius:10px;color:#dce9fa;background:#14253c;overflow:auto;line-height:1.7}pre code{color:inherit;background:none;padding:0;white-space:pre}h1,h2,h3{color:#132e52;line-height:1.6}h1{font-size:27px}h2{margin:36px 0 14px;font-size:21px;border-bottom:1px solid #d9e2ef;padding-bottom:10px;scroll-margin-top:190px}h3{font-size:17px;margin-top:27px}p{margin:13px 0}header{background:#102b4e;color:#fff;padding:24px 34px}header h1{margin:0;color:#fff;font-size:25px}header p{color:#bdcee4;margin:5px 0 0}.meta{display:flex;gap:12px;flex-wrap:wrap;margin-top:15px}.meta span{background:#254469;padding:4px 11px;border-radius:6px;font-size:12px}.toolbar{position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid #d4deeb;box-shadow:0 4px 15px #1232}.toolbar-inner{max-width:1540px;margin:auto;padding:13px 26px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}nav{display:flex;gap:5px;flex-wrap:wrap}nav button,.secondary{border:1px solid #d5dfec;background:#fff;color:#254569;padding:8px 13px;border-radius:7px}nav button.active{background:#136c9c;border-color:#136c9c;color:white}.search{flex:1;min-width:230px;border:1px solid #bbcadd;border-radius:8px;padding:10px 13px}.shell{max-width:1540px;margin:auto;padding:25px;display:grid;grid-template-columns:235px minmax(0,1fr);gap:22px}aside{align-self:start;position:sticky;top:125px;background:#fff;border:1px solid #dae3ef;border-radius:12px;padding:16px;font-size:12px;max-height:calc(100vh - 150px);overflow:auto}aside a{display:block;margin-bottom:10px}aside .toc-sub{padding-left:12px;color:#63738c}main{min-width:0}.panel{display:none}.panel.active{display:block}.card,.document{background:white;border:1px solid #dbe4ef;border-radius:12px;padding:25px;margin-bottom:18px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}.stat{background:#fff;border:1px solid #dbe4ef;border-radius:10px;padding:15px}.stat strong{font-size:26px;color:#135c8a;display:block}.muted{color:#61748c;font-size:12px}.note{padding:12px 15px;background:#eaf5fb;border-left:4px solid #1784b5;border-radius:5px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:12px}th,td{text-align:left;padding:11px 12px;vertical-align:top;border-bottom:1px solid #e1e8f1;overflow-wrap:anywhere}th{background:#eff4fa;color:#34506e;white-space:nowrap}td code{font-size:11px}tbody tr:hover{background:#f6f9fd}.file-button{border:0;background:none;color:#1260a6;padding:0;text-align:left;font:12px Consolas,monospace;overflow-wrap:anywhere}.count{font-weight:700;color:#59708a}.filters{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}select{border:1px solid #cbd8e6;padding:8px;border-radius:6px;background:white}.split{display:grid;grid-template-columns:1fr 1fr;gap:15px}.badge{display:inline-block;font-size:10px;padding:2px 7px;background:#e7f1f9;color:#22577c;border-radius:4px;margin:3px 3px 3px 0}.method{font-family:Consolas,monospace;font-size:11px;border-bottom:1px solid #e5ebf4;padding:7px 0;overflow-wrap:anywhere}details{border:1px solid #dae3ee;border-radius:8px;margin:10px 0;padding:12px}summary{cursor:pointer;color:#174876;font-weight:600}.pager{display:flex;gap:12px;align-items:center;margin-top:15px}.empty{color:#718198;padding:15px}.diagram svg{width:100%;height:auto}.downloads{display:flex;gap:12px;flex-wrap:wrap}.downloads a{background:#eaf2fa;padding:7px 10px;border-radius:5px}.selected{border-left:4px solid #1680a6}#file-detail:empty{display:none}.hidden{display:none!important}.external{color:#777}.nowrap{white-space:nowrap}footer{font-size:12px;color:#697c94;text-align:center;padding:20px}.wrap-code{white-space:pre-wrap;overflow-wrap:anywhere}.model-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.model-grid .card{margin:0}.inventory-summary{font-size:12px}.print-only{display:none}
.shell.wide{grid-template-columns:1fr}#file-detail{scroll-margin-top:140px}
@media(max-width:1000px){.shell{grid-template-columns:1fr}aside{display:none}.model-grid,.split{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,1fr)}header{padding:20px}.toolbar-inner{padding:10px}.shell{padding:14px}.card,.document{padding:18px}}
@media print{body{background:white;font-size:11px}.toolbar,aside,.filters,.pager,.downloads,.no-print{display:none!important}.shell{display:block;padding:0}.panel{display:none!important}.panel.active{display:block!important}.card,.document{border:0;padding:0;box-shadow:none}.stats{grid-template-columns:repeat(4,1fr)}header{color:#132e52;background:white;padding:0 0 12px}header h1{color:#132e52}header p{color:#555}.meta span{color:#333;background:#eee}h2,h3{break-after:avoid}tr,pre,details{break-inside:avoid}.print-only{display:block}pre{white-space:pre-wrap;color:#111;background:#f3f3f3}a{color:#174876}footer{display:none}details>*{display:block!important}.model-grid{display:block}.model-grid .card{margin:20px 0}.diagram{break-inside:avoid}}
'''

JS=r'''
const DATA=__DATA__;
const files=new Map(DATA.files.map(f=>[f.path,f]));
const $=id=>document.getElementById(id);
const esc=x=>String(x??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fileButton=p=>files.has(p)?`<button class="file-button" data-file="${esc(p)}">${esc(p)}</button>`:`<code class="external">${esc(p)}</code>`;
const bytes=n=>n>1048576?(n/1048576).toFixed(2)+' MiB':n>1024?(n/1024).toFixed(1)+' KiB':n+' B';
let current='guide',query='',page=0,selected=null;
const pageSize=40;
function table(heads,rows){return `<div class="table-wrap"><table><thead><tr>${heads.map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.join('')}</tbody></table></div>`;}
function pager(total){let pages=Math.max(1,Math.ceil(total/pageSize));return `<div class="pager"><button class="secondary" data-page="${Math.max(0,page-1)}" ${page===0?'disabled':''}>Previous</button><span>${page+1} / ${pages} · ${total.toLocaleString()} results</span><button class="secondary" data-page="${Math.min(pages-1,page+1)}" ${page>=pages-1?'disabled':''}>Next</button></div>`;}
function match(x){return JSON.stringify(x).toLowerCase().includes(query.toLowerCase());}
function activate(name,hash=true){current=name;page=0;document.querySelectorAll('.panel').forEach(p=>p.classList.toggle('active',p.id==='panel-'+name));document.querySelectorAll('nav button').forEach(b=>b.classList.toggle('active',b.dataset.tab===name));$('toc').classList.toggle('hidden',name!=='guide');document.querySelector('.shell').classList.toggle('wide',name!=='guide');if(hash)history.replaceState(null,'','#view='+name);render();}
function edgesTable(edges,direction){const unique=[];const seen=new Set();for(const e of edges){let p=direction==='outgoing'?e.target:e.source;let k=p+'|'+e.kind+'|'+e.evidence;if(!seen.has(k)){seen.add(k);unique.push(e);}}return unique.length?table([direction==='outgoing'?'Uses / target':'Used by / caller','Reference','Evidence'],unique.map(e=>`<tr><td>${fileButton(direction==='outgoing'?e.target:e.source)}</td><td>${esc(e.kind)}<br><span class="muted">${esc(e.confidence)} · source line ${e.line}</span></td><td><code>${esc(e.evidence)}</code></td></tr>`)):'<p class="empty">No matching static references detected. Convention/dynamic/runtime use can still exist.</p>';}
function showFile(path,scroll=true){const f=files.get(path);if(!f)return;selected=path;activate('files',false);history.replaceState(null,'','#file='+encodeURIComponent(path));const models=DATA.models.filter(m=>m.file===path);const methods=f.methods.map(m=>`<div class="method"><span class="badge">${m.visibility}${m.static?' static':''}</span> ${esc(m.name)}(${esc(m.parameters.join(', '))})${m.return_type?': '+esc(m.return_type):''}<span class="muted"> · line ${m.line}</span></div>`).join('');
let cls=f.classes.map(c=>`<p><code>${esc(c.kind)} ${esc(c.name)}</code>${c.extends?'<br><span class="muted">extends '+esc(c.extends)+'</span>':''}</p>${Object.keys(c.cases||{}).length?'<pre><code>'+esc(JSON.stringify(c.cases,null,2))+'</code></pre>':''}`).join('');
$('file-detail').innerHTML=`<div class="card selected"><div class="filters"><strong>File reference</strong><button class="secondary" id="close-detail">Close</button><a href="../../${path.split('/').map(encodeURIComponent).join('/')}" target="_blank" rel="noopener">Open local source</a></div><h2>${esc(path)}</h2><p>${esc(f.role)}</p><p class="muted">${esc(f.category)} · ${bytes(f.bytes)} · ${f.outgoing.length} outgoing / ${f.incoming.length} incoming references</p>${cls}${f.routes.length?'<h3>Registered routes</h3>'+table(['Method','URI','Name'],f.routes.map(r=>`<tr><td>${esc(r.method)}</td><td><code>${esc(r.uri)}</code></td><td>${esc(r.name)}</td></tr>`)):''}${models.length?'<p class="note">Model table: <strong>'+esc(models[0].table)+'</strong>. Database tab එකේ relationships සහ migrations බලන්න.</p>':''}<div class="split"><div><h3>Configuration keys</h3>${f.config_keys.map(x=>'<code>'+esc(x)+'</code>').join(' ')||'<p class="muted">None detected</p>'}</div><div><h3>Explicit permissions</h3>${f.permissions.map(x=>'<code>'+esc(x.name)+'</code>').join(' ')||'<p class="muted">None detected; route middleware/dynamic checks may apply</p>'}</div></div>${methods?'<details><summary>Methods ('+f.methods.length+')</summary>'+methods+'</details>':''}${f.wire_actions.length?'<details><summary>Blade wire actions ('+f.wire_actions.length+')</summary>'+table(['Action expression','Line'],f.wire_actions.map(a=>`<tr><td><code>${esc(a.action)}</code></td><td>${a.line}</td></tr>`))+'</details>':''}${f.tests.length?'<details><summary>Declared tests ('+f.tests.length+')</summary>'+table(['Description','Line'],f.tests.map(t=>`<tr><td>${esc(t.description)}</td><td>${t.line}</td></tr>`))+'</details>':''}<h3>Uses — මේ file එක භාවිතා කරන dependencies</h3>${edgesTable(f.outgoing,'outgoing')}<h3>Used by — මේ file එකට reference කරන files</h3>${edgesTable(f.incoming,'incoming')}${f.external_imports.length?'<details><summary>Framework / external imports</summary><pre><code>'+esc(f.external_imports.join('\n'))+'</code></pre></details>':''}</div>`;
if(f.unresolved_views.length)$('file-detail').innerHTML='<div class="card note"><strong>Unresolved literal view references</strong><pre><code>'+esc(JSON.stringify(f.unresolved_views,null,2))+'</code></pre></div>'+$('file-detail').innerHTML;
if(scroll)$('file-detail').scrollIntoView({behavior:'smooth',block:'start'});}
function renderFiles(){let group=$('file-group').value;let rows=DATA.files.filter(f=>(!group||f.group===group)&&match({path:f.path,role:f.role,classes:f.classes.map(c=>c.name),methods:f.methods.map(m=>m.name),table:f.table,config:f.config_keys}));if(page*pageSize>=rows.length)page=0;$('file-list').innerHTML=table(['File','Responsibility','Uses / used by'],rows.slice(page*pageSize,(page+1)*pageSize).map(f=>`<tr><td>${fileButton(f.path)}<br><span class="muted">${bytes(f.bytes)}</span></td><td>${esc(f.role)}</td><td class="nowrap">${f.outgoing.length} / ${f.incoming.length}</td></tr>`))+pager(rows.length);}
function renderRoutes(){let scope=$('route-scope').value;let rows=DATA.routes.filter(r=>(!scope||(scope==='app'?!!r.definition.path:!r.definition.path))&&match(r));$('route-list').innerHTML=table(['Method / URL','Route name','Actual handler','Middleware / constraints'],rows.map(r=>`<tr><td><span class="badge">${esc(r.method)}</span><br><code>/${esc(r.uri==='/'?'':r.uri)}</code></td><td>${esc(r.name||'unnamed')}<br>${r.definition.path?fileButton(r.definition.path)+'<span class="muted">:'+r.definition.line+'</span>':'<span class="muted">framework/package registration</span>'}</td><td>${fileButton(r.handler_file||r.action)}${r.component?'<br><code>'+esc(r.component)+'</code>':''}${r.resolved_component?'<br><span class="muted">'+esc(r.resolved_component)+'</span>':''}</td><td>${r.middleware.map(x=>'<div>'+esc(x)+'</div>').join('')}${Object.keys(r.constraints||{}).length?'<details><summary>Constraints / defaults</summary><pre><code>'+esc(JSON.stringify({constraints:r.constraints,defaults:r.defaults},null,2))+'</code></pre></details>':''}</td></tr>`));$('route-count').textContent=rows.length+' registered routes';}
function relationTarget(m,rel){let arg=rel.arguments[0];if(arg&&arg.class){let cls=arg.class==='self'?m.class:arg.class;let model=DATA.models.find(x=>x.class===cls);return model?fileButton(model.file):'<code>'+esc(cls)+'</code>';}return '<span class="muted">dynamic / polymorphic</span>';}
function renderDatabase(){let rows=DATA.models.filter(match);$('model-list').innerHTML=rows.map(m=>`<article class="card"><h3>${esc(m.table)}</h3>${fileButton(m.file)}<p class="muted">Primary key: ${esc(m.key)} · Soft deletes: ${m.soft_deletes?'yes':'no'}</p>${table(['Relation method','Type','Target / arguments'],m.relations.map(r=>`<tr><td><code>${esc(r.method)}()</code></td><td>${esc(r.type)}</td><td>${relationTarget(m,r)}<br><code>${esc(JSON.stringify(r.arguments))}</code></td></tr>`))}<details><summary>Fields / casts</summary><pre><code>${esc(JSON.stringify({fillable:m.fillable,casts:m.casts},null,2))}</code></pre><p class="muted">Empty fillable may mean guarded-based assignment; it does not mean no database columns.</p></details><details><summary>Schema migrations (${m.migrations.length})</summary>${m.migrations.map(x=>'<p>'+fileButton(x.file)+'<br><span class="badge">'+esc(x.operation)+' '+esc(x.table)+'</span></p>'+table(['Column declaration','Arguments'],x.columns.map(c=>`<tr><td>${esc(c.type)}</td><td><code>${esc(JSON.stringify(c.arguments))}</code></td></tr>`))).join('')||'<p class="muted">No literal-table migration matched; check dynamic package migrations.</p>'}</details></article>`).join('');
$('migration-list').innerHTML=DATA.files.filter(f=>f.path.startsWith('database/migrations/')&&match({path:f.path,schema:DATA.schema.filter(x=>x.file===f.path)})).map(f=>'<details><summary>'+esc(f.path)+'</summary>'+fileButton(f.path)+'<pre><code>'+esc(JSON.stringify(DATA.schema.filter(x=>x.file===f.path),null,2))+'</code></pre></details>').join('');}
function renderFolders(){let roots=DATA.folders.filter(x=>x.path!=='.'&&!x.path.includes('/'));$('folder-list').innerHTML=table(['Root folder','Files / size','Responsibility'],roots.map(f=>`<tr><td><code>${esc(f.path)}/</code></td><td>${f.files.toLocaleString()}<br>${bytes(f.bytes)}</td><td>${esc(DATA.folder_roles[f.path]||'Project folder')}</td></tr>`))+'<h3>Local configuration snapshot</h3><p class="muted">Safe whitelist only; actual .env values/credentials excluded. Hosting configuration may differ.</p>'+table(['Configuration key','Local value'],Object.entries(DATA.configuration).map(([key,value])=>`<tr><td><code>${esc(key)}</code></td><td><code>${esc(JSON.stringify(value))}</code></td></tr>`));}
function renderPackages(){let rows=DATA.packages.filter(match);$('package-list').innerHTML=table(['PHP package / locked version','Scope','Purpose / requirements'],rows.map(p=>`<tr><td><code>${esc(p.name)}</code><br>${esc(p.version)}</td><td>${p.development?'Development':'Runtime'}</td><td>${esc(p.description)}<details><summary>Requires</summary><pre><code>${esc(JSON.stringify(p.requires,null,2))}</code></pre></details></td></tr>`));$('node-list').innerHTML='<pre><code>'+esc(JSON.stringify(DATA.node_manifest,null,2))+'</code></pre>';}
function renderInventory(){let category=$('inventory-category').value;let rows=DATA.inventory.filter(x=>(!category||x.category===category)&&match(x));if(page*pageSize>=rows.length)page=0;$('inventory-list').innerHTML=table(['Path','Category','Size / target'],rows.slice(page*pageSize,(page+1)*pageSize).map(x=>`<tr><td>${fileButton(x.path)}</td><td>${esc(x.category)}</td><td>${bytes(x.bytes)}${x.target?'<br>'+esc(x.target):''}${'matches_source_sha256' in x?'<br>Source hash match: '+x.matches_source_sha256:''}</td></tr>`))+pager(rows.length);}
function render(){if(current==='files')renderFiles();if(current==='routes')renderRoutes();if(current==='database')renderDatabase();if(current==='folders')renderFolders();if(current==='packages')renderPackages();if(current==='inventory')renderInventory();$('search-hint').textContent=current==='guide'?'Guide search: Ctrl+F · වෙනත් tabs සඳහා search box භාවිතා කරන්න':'Search applies to '+current;}
document.addEventListener('click',event=>{let file=event.target.closest('[data-file]');if(file){event.preventDefault();showFile(file.dataset.file);return;}let tab=event.target.closest('[data-tab]');if(tab){activate(tab.dataset.tab);return;}let pg=event.target.closest('[data-page]');if(pg){page=Number(pg.dataset.page);render();return;}if(event.target.id==='close-detail'){$('file-detail').innerHTML='';selected=null;history.replaceState(null,'','#view=files');}if(event.target.id==='print-report')window.print();});
$('global-search').addEventListener('input',event=>{query=event.target.value;page=0;render();});
for(const id of ['file-group','route-scope','inventory-category'])$(id).addEventListener('change',()=>{page=0;render();});
$('file-group').innerHTML='<option value="">All project folders</option>'+[...new Set(DATA.files.map(f=>f.group))].sort().map(g=>`<option>${esc(g)}</option>`).join('');
$('inventory-category').innerHTML='<option value="">All categories</option>'+[...new Set(DATA.inventory.map(f=>f.category))].sort().map(g=>`<option>${esc(g)}</option>`).join('');
function readHash(){let hash=new URLSearchParams(location.hash.slice(1));if(hash.has('file'))showFile(hash.get('file'),false);else activate(hash.get('view')||'guide',false);}
window.addEventListener('hashchange',readHash);readHash();
'''
json_inline=json.dumps(DATA,ensure_ascii=False,separators=(',',':')).replace('</',r'<\/').replace('\u2028','\\u2028').replace('\u2029','\\u2029')
JS=JS.replace('__DATA__',json_inline)
(OUT/'report-ui.js').write_text(JS,encoding='utf-8')
toc=''.join('<a class="'+('toc-sub' if level>2 else '')+'" href="#'+ident+'">'+ESC(title)+'</a>' for level,ident,title in headings if level in [2,3])
counts=DATA['counts']
stats=''.join('<div class="stat"><strong>'+f'{counts[key]:,}'+'</strong>'+label+'</div>' for key,label in [('project_files','project files'),('routes','registered routes'),('models','Eloquent models'),('edges','detected references')])
downloads='<div class="downloads">'+''.join('<a href="'+name+'">'+label+'</a>' for name,label in [('ARCHITECTURE-REPORT.si.md','Full Markdown'),('all-files.csv','All files CSV'),('dependencies.csv','Connections CSV'),('routes.csv','Routes CSV'),('folders.csv','Folders CSV'),('request-flow.svg','Flow diagram SVG')])+'</div>'
template='''<!doctype html><html lang="si"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Offline architecture and incident reference for the sch_of_slsc local Laravel project."><title>sch_of_slsc · Architecture reference</title><style>@font-face{font-family:DocSinhala;src:url(data:font/woff2;base64,__FONT__) format('woff2');font-display:swap}__CSS__</style></head><body>
<header><h1>sch_of_slsc · Project reference</h1><p>Files, routes, services, database relationships සහ හදිසි troubleshooting guide</p><div class="meta"><span>Local snapshot · 2026-10-22 · Asia/Colombo</span><span>Offline · No external requests</span><span>Secrets / live database content excluded</span></div></header>
<div class="toolbar"><div class="toolbar-inner"><nav aria-label="Report sections">__NAV__</nav><input id="global-search" class="search" type="search" placeholder="File, URL, model, method හෝ keyword සොයන්න…" aria-label="Search active report section"><button class="secondary" id="print-report">Print / PDF</button><span class="muted" id="search-hint"></span></div></div>
<div class="shell"><aside id="toc"><strong>Guide contents</strong>__TOC__</aside><main>
<section id="panel-guide" class="panel active">__STATS__<div class="card diagram">__SVG__</div><div class="card">__DOWNLOADS__</div><article class="document">__GUIDE__</article></section>
<section id="panel-files" class="panel"><div id="file-detail"></div><div class="card"><h2>File-by-file connections</h2><p>File එකක් තෝරා responsibility, methods, uses, used-by සහ source line evidence බලන්න.</p><div class="filters"><select id="file-group" aria-label="Filter project folder"></select><span class="muted">Imports, conventions සහ executed runtime metadata වෙන වෙනම labels ඇත.</span></div><div id="file-list"></div></div></section>
<section id="panel-routes" class="panel"><div class="card"><h2>URL → actual handler → middleware</h2><div class="filters"><select id="route-scope" aria-label="Filter route scope"><option value="">All registered routes</option><option value="app">Application source routes</option><option value="package">Framework / authentication / package routes</option></select><span id="route-count" class="muted"></span></div><div id="route-list"></div></div></section>
<section id="panel-database" class="panel"><div class="card"><h2>Database and model connections</h2><p class="note">Model metadata සහ migration declarations පමණි. Live database rows/schema export කර නැහැ. Migration operations වල up/down declarations දෙකම තිබිය හැකියි.</p></div><div id="model-list" class="model-grid"></div><div class="card"><h2>All migration files</h2><div id="migration-list"></div></div></section>
<section id="panel-folders" class="panel"><div class="card"><h2>Folder responsibilities</h2><p>Directory links follow කර නැහැ; public/storage uploads duplicate ලෙස ගණන් කර නැහැ. Full nested directories <a href="folders.csv">folders.csv</a> තුළ ඇත.</p><div id="folder-list"></div></div></section>
<section id="panel-packages" class="panel"><div class="card"><h2>PHP dependencies — locked versions</h2><p>composer.lock runtime/development classification. Production installed tree එක වෙනස් විය හැකියි.</p><div id="package-list"></div><h2>Node dependencies / build commands</h2><p>මෙහි versions package.json requested ranges ය; exact resolution package-lock.json තුළ ඇත. Leadership compiler සඳහා Node/Tailwind runtime එකට අවශ්‍යයි.</p><div id="node-list"></div></div></section>
<section id="panel-inventory" class="panel"><div class="card"><h2>All files — metadata inventory</h2><p class="note">Vendor, Node modules, Git metadata, generated caches සහ uploaded files සඳහා path/size inventory පමණි. ඒවායේ source/data contents recursively analyze කර නැහැ.</p><div class="filters"><select id="inventory-category" aria-label="Filter inventory category"></select></div><div id="inventory-list"></div></div></section>
</main></div><footer>Generated from local working tree · docs/architecture/tools · Regenerate after code changes</footer><script>__SCRIPT__</script></body></html>'''
nav=''.join('<button data-tab="'+name+'" class="'+('active' if name=='guide' else '')+'">'+label+'</button>' for name,label in [('guide','Guide'),('files','Files'),('routes','Routes'),('database','Database'),('folders','Folders'),('packages','Packages'),('inventory','All files')])
for key,value in {'__FONT__':font,'__CSS__':CSS,'__NAV__':nav,'__TOC__':toc,'__STATS__':stats,'__SVG__':SVG,'__DOWNLOADS__':downloads,'__GUIDE__':guide_html,'__SCRIPT__':JS}.items(): template=template.replace(key,value)
(OUT/'index.html').write_text(template,encoding='utf-8')

def mdcell(value): return str(value).replace('|','\\|').replace('\n',' ')
def ident(path): return 'file-'+hashlib.sha1(path.encode()).hexdigest()[:12]
def mdfile(path): return '['+path+'](#'+ident(path)+')' if path in FILES else '`'+path+'`'
report=[guide,'\n# Appendix A — Root folder inventory\n','| Folder | Files | Bytes | Responsibility |','| --- | ---: | ---: | --- |']
for row in DATA['folders']:
    if row['path']!='.' and '/' not in row['path']: report.append(f'| `{row["path"]}/` | {row["files"]:,} | {row["bytes"]:,} | {FOLDER_ROLES.get(row["path"], "")} |')
report+=['\n# Appendix B — Complete registered route catalog\n','Middleware entries are the locally expanded route:list output. Handler resolution does not execute authenticated actions.\n','| Method | URI | Name | Handler source | Middleware |','| --- | --- | --- | --- | --- |']
for row in DATA['routes']:
    report.append('| '+' | '.join(mdcell(x) for x in [row['method'],'/'+('' if row['uri']=='/' else row['uri']),row['name'] or '(unnamed)',row['handler_file'] or row['action'],'; '.join(row['middleware'])])+' |')
report+=['\n# Appendix C — Models, tables and relationships\n']
for model in DATA['models']:
    report += ['## '+model['table'],mdfile(model['file'])+' · key: `'+model['key']+'` · soft deletes: '+str(model['soft_deletes']), '', '| Relationship | Type | Arguments |','| --- | --- | --- |']
    for rel in model['relations']: report.append('| '+rel['method']+' | '+rel['type']+' | '+mdcell(json.dumps(rel['arguments'],ensure_ascii=False))+' |')
    report += ['', 'Schema migration files: '+', '.join(dict.fromkeys(mdfile(x['file']) for x in model['migrations'])), 'Casts: `'+mdcell(json.dumps(model['casts'],ensure_ascii=False))+'`','']
report+=['\n# Appendix D — Every project file and detected connections\n','580-style file catalog count is generated from the snapshot; dependency/generated/private content is in all-files.csv. Imports/declarations and conditional/conventional references do not prove that a branch executes on every request.\n'.replace('580-style',str(counts['project_files'])+'-entry')]
for file in DATA['files']:
    report += ['<a id="'+ident(file['path'])+'"></a>','## '+file['path'],'',file['role'],'',f'Category: {file["category"]}. Size: {file["bytes"]:,} bytes.','']
    for cls in file['classes']:
        report.append('Symbol: `'+cls['name']+'`'+(' extends `'+cls['extends']+'`' if cls['extends'] else '')+'.')
        if cls.get('cases'): report.append('Enum cases: `'+json.dumps(cls['cases'],ensure_ascii=False)+'`.')
    public=[m['name']+'():'+str(m['line']) for m in file['methods'] if m['visibility']=='public']
    if public: report.append('Public methods (source line): '+', '.join('`'+x+'`' for x in public)+'.')
    if file['routes']: report.append('Registered URLs: '+', '.join('`'+r['uri']+'` ('+str(r['name'])+')' for r in file['routes'])+'.')
    if file['config_keys']: report.append('Config reads: '+', '.join('`'+x+'`' for x in file['config_keys'])+'.')
    if file['unresolved_views']: report.append('Unresolved literal views: '+', '.join('`'+x['name']+'` at line '+str(x['line']) for x in file['unresolved_views'])+'. Check conditional/package fallback handling.')
    if file['permissions']: report.append('Explicit permission literals: '+', '.join('`'+x+'`' for x in sorted({p['name'] for p in file['permissions']}))+'.')
    for direction,label,target in [('outgoing','Uses','target'),('incoming','Used by','source')]:
        grouped={}
        for edge in file[direction]: grouped.setdefault(edge[target],set()).add(edge['kind'])
        report += ['', '**'+label+':**']
        if grouped:
            for path,kinds in sorted(grouped.items()): report.append('- '+mdfile(path)+' — '+', '.join(sorted(kinds)))
        else: report.append('No static references detected; this is not proof of non-use.')
    if file['tests']: report+=['','Declared tests:']+['- '+x['description']+' (line '+str(x['line'])+')' for x in file['tests']]
    report.append('')
report+=['\n# Appendix E — PHP package lock\n','| Package | Version | Scope | Purpose |','| --- | --- | --- | --- |']
for package in DATA['packages']: report.append('| '+' | '.join(mdcell(x) for x in [package['name'],package['version'],'development' if package['development'] else 'runtime',package['description']])+' |')
(OUT/'ARCHITECTURE-REPORT.si.md').write_text('\n'.join(report),encoding='utf-8')

(OUT/'README.md').write_text('''# sch_of_slsc architecture reference

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
''',encoding='utf-8')
print(json.dumps({'project_files':counts['project_files'],'html_bytes':(OUT/'index.html').stat().st_size,'markdown_bytes':(OUT/'ARCHITECTURE-REPORT.si.md').stat().st_size,'guide_sections':len(headings),'outputs':[p.name for p in OUT.iterdir() if p.is_file()]},ensure_ascii=True))
