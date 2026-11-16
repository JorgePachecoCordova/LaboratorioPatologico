<style>
/* Jerarquía del formulario, sin alterar la plantilla impresa. */
.page-scroll .ap-refined { max-width:1440px; margin:0 auto; padding:24px; }
.ap-refined .ap-header { margin-bottom:20px; gap:16px; }
.ap-refined .ap-header h1 { font-size:26px; letter-spacing:-.5px; }
.ap-refined .ap-header p { font-size:13px; }
.ap-refined .ap-search-panel { display:flex; align-items:center; justify-content:space-between; gap:20px; flex:0 0 auto; padding:16px 20px; margin-bottom:16px; border:1px solid #dce6e0; border-radius:12px; background:#fff; box-shadow:0 4px 20px #163e2610; }
.ap-search-copy h2 { margin:0; font-size:15px; color:#244538; }
.ap-search-copy p { margin:4px 0 0; color:#65776e; font-size:12px; }
.ap-refined .ap-search-form { display:flex; gap:8px; flex:0 1 540px; min-width:0; }
.ap-search-form .ap-search-input { width:100%; min-width:0; height:42px; padding:0 12px; border:1px solid #cadbd1; border-radius:8px; background:#fff; color:#233e31; font-size:13px; }
.ap-search-form button { border:0; border-radius:8px; padding:0 20px; background:#16844e; color:#fff; font-weight:600; cursor:pointer; }
.ap-search-form :is(input,button):focus-visible { outline:2px solid #16844e; outline-offset:3px; }
html[data-theme="light"] .ap-refined .ap-search-panel { background:#edf5f0; border:1px solid #8eb49f; box-shadow:0 3px 12px #163e2612; }
html[data-theme="light"] .ap-search-copy h2 { color:#174b32; font-size:18px; font-weight:700; }
html[data-theme="light"] .ap-search-copy p { color:#3b5547; font-size:14px; line-height:1.5; }
html[data-theme="light"] .ap-search-form .ap-search-input { height:46px; border:2px solid #7c9f8b; background:#fff; color:#193d29; font-size:14px; }
html[data-theme="light"] .ap-search-form .ap-search-input::placeholder { color:#52675a; opacity:1; }
html[data-theme="light"] .ap-search-form button { background:#126c40; font-size:14px; padding:0 24px; }
html[data-theme="dark"] .ap-search-panel { background:#182327; border-color:#405159; box-shadow:none; }
html[data-theme="dark"] .ap-search-copy h2 { color:#eef8f3; }
html[data-theme="dark"] .ap-search-copy p { color:#b9ccc3; }
html[data-theme="dark"] .ap-search-form input { background:#111c20; border-color:#4b6068; color:#f5faf8; }
@media(max-width:900px) {
 .ap-refined .ap-search-panel { align-items:stretch; flex-direction:column; gap:12px; padding:16px; }
 .ap-refined .ap-search-form { flex:auto; width:100%; }
}
.ap-refined .ap-card { box-shadow:0 4px 20px #163e2610; border-color:#dce6e0; }
.ap-refined .ap-title { background:#fff; border-bottom:1px solid #e5ece8; padding:16px 24px; }
.ap-refined .ap-title strong { font-size:12px; letter-spacing:.6px; }
.ap-refined .ap-title span { background:#eef6f1; padding:5px 9px; border-radius:5px; font-size:10px; }
.ap-refined .ap-section-caption { display:flex; align-items:center; gap:16px; padding:24px 24px 4px; }
.ap-refined .ap-section-caption h2 { margin:0; color:#244538; font-size:16px; font-weight:600; letter-spacing:-.2px; }
.ap-refined .ap-grid { padding:14px 24px 22px; gap:16px; }
.ap-refined .ap-grid label,.ap-refined .ap-results label { font-size:12px; line-height:22px; color:#42594e; }
.ap-refined .ap-grid.identity { grid-template-columns:repeat(12,minmax(0,1fr)); }
.ap-refined .identity > label:has([name="report_date"]) { grid-column:span 3; }
.ap-refined .identity > .patient { grid-column:span 7; }
.ap-refined .identity > label:has([name="hospital_service_id"]) { grid-column:span 4; }
.ap-refined .identity > .doctor { grid-column:span 6; }
.ap-refined .ap-grid :is(input,select) { height:40px; }
.ap-refined .clinical { background:#fafcfb; }
.ap-refined .clinical textarea { height:76px; }
.ap-refined .ap-results { grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px 20px; padding:14px 24px 24px; }
.ap-refined .ap-results label { font-weight:600!important; font-size:13px; }
.ap-refined .ap-results label:has([name="diagnosis"]), .ap-refined .ap-results label:has([name="notes"]) { grid-column:1/-1; padding-top:4px; }
.ap-refined .ap-results textarea[name="macroscopic_description"],
.ap-refined .ap-results textarea[name="microscopic_description"] { height:180px!important; min-height:180px!important; line-height:1.65; }
.ap-refined .ap-results textarea[name="diagnosis"], .ap-refined .ap-results textarea[name="notes"] { height:90px!important; min-height:90px!important; }
.ap-refined .ap-actions { padding:16px 24px; background:#fafcfb; }
.ap-refined .ap-btn { min-height:40px; padding:0 18px; font-size:12px; }
.ap-refined .ap-actions .blue { background:#fff; color:#256647; border:1px solid #bdcfc4; }
.ap-refined .ap-record { padding:14px 24px; background:#fff; color:#65776e; }
html[data-theme="dark"] .ap-refined :is(.ap-title,.ap-record) { background:#182327; }
html[data-theme="dark"] .ap-refined :is(.clinical,.ap-actions) { background:#1b292e; }
html[data-theme="dark"] .ap-refined :is(.ap-section-caption h2,.ap-grid label,.ap-results label) { color:#d9e9e1; }
html[data-theme="dark"] .ap-refined :is(.ap-section-caption span,.ap-title span) { background:#203e32; color:#72e0aa; }
html[data-theme="dark"] .ap-refined .ap-actions .blue { background:#203e32; color:#d9e9e1; }
@media(max-width:900px) {
 .page-scroll .ap-refined { padding:14px; }
 .ap-refined .ap-grid.identity { grid-template-columns:repeat(2,minmax(0,1fr)); }
 .ap-refined .identity > label,.ap-refined .identity > .doctor,
 .ap-refined .identity > label:has([name="report_date"]),
 .ap-refined .identity > label:has([name="hospital_service_id"]) { grid-column:auto; }
 .ap-refined .identity > .patient { grid-column:1/-1; }
 .ap-refined .clinical { grid-template-columns:1fr; }
 .ap-refined .ap-section-caption { padding:18px 16px 0; }
 .ap-refined :is(.ap-grid,.ap-results,.ap-title,.ap-actions,.ap-record) { padding-left:16px; padding-right:16px; }
}
@media(max-width:600px) {
 .ap-refined .ap-grid.identity,.ap-refined .ap-results { grid-template-columns:minmax(0,1fr); }
 .ap-refined .ap-header h1 { font-size:23px; }
 .ap-refined .ap-record form { min-width:0; }
}
</style>
