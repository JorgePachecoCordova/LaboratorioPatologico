<style>
.cfg-page,
.cfg-page * { box-sizing: border-box; }

.cfg-page {
    width: 100%;
    height: 100%;
    padding: 16px 22px 30px;
    color: #263d32;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.cfg-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #dfe8e3;
}

.cfg-header-accent {
    width: 4px;
    align-self: stretch;
    min-height: 52px;
    border-radius: 999px;
    background: #17824f;
}

.cfg-header h1 {
    margin: 0;
    color: #173c2a;
    font-size: clamp(27px, 2vw, 34px);
    line-height: 1.05;
    letter-spacing: -.6px;
    font-weight: 650;
}

.cfg-header p {
    margin: 4px 0 0;
    color: #697a71;
    font-size: 12px;
    letter-spacing: .35px;
    text-transform: uppercase;
}

.cfg-eyebrow { display:block;margin-bottom:4px;color:#16804d;font-size:10px;font-weight:650;letter-spacing:1.6px; }

.cfg-alert {
    margin-bottom: 14px;
    padding: 11px 14px;
    border-radius: 10px;
    border-left: 4px solid #17824f;
    background: #effcf5;
    color: #0f6f42;
    font-size: 13px;
}

.cfg-alert.error {
    border-left-color: #dc2626;
    background: #fff4f4;
    color: #9f1d1d;
}

.cfg-nav {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.cfg-nav a {
    padding: 9px 14px;
    border: 1px solid #d9e4dd;
    border-radius: 8px;
    background: #fff;
    color: #40564b;
    text-decoration: none;
    font-size: 12px;
    font-weight: 550;
}

.cfg-nav a:hover {
    border-color: #17824f;
    color: #0f6f42;
    background: #f2faf6;
}

.cfg-nav a.active {
    border-color: #17824f;
    background: #e9f6ef;
    color: #106d40;
    box-shadow: 0 0 0 2px rgba(23, 130, 79, .06);
}

.cfg-section {
    margin-bottom: 16px;
    padding: 15px 16px;
    border: 1px solid #e5ece8;
    border-radius: 13px;
    background: #fff;
    box-shadow: 0 6px 18px rgba(22, 73, 45, .05);
    display: none;
    min-height: 0;
    overflow: auto;
}

.cfg-section.active { display: block; flex: 1; margin-bottom: 0; }

.cfg-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.cfg-section-head h2 {
    margin: 0;
    color: #0f7e48;
    font-size: 17px;
    font-weight: 620;
    letter-spacing: -.2px;
}

.cfg-section-head small {
    display: block;
    margin-top: 3px;
    color: #718096;
}

.cfg-create {
    margin-bottom: 12px;
    border: 1px solid #dce8e1;
    border-radius: 11px;
    overflow: hidden;
}

.cfg-create summary {
    cursor: pointer;
    padding: 10px 12px;
    list-style: none;
    background: #f5faf7;
    color: #0f6f42;
    font-size: 13px;
    font-weight: 600;
}

.cfg-create summary::-webkit-details-marker { display: none; }

.cfg-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    padding: 12px;
}

.cfg-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.cfg-field.wide { grid-column: span 2; }
.cfg-field.full { grid-column: 1 / -1; }

.cfg-user-form .cfg-field > label {
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 22px;
    line-height: 1.4;
}
.cfg-user-form .cfg-field > .cfg-input,
.cfg-user-form .cfg-password-wrap > .cfg-input {
    height: 40px;
    box-sizing: border-box;
}

.cfg-field label {
    color: #3b5146;
    font-size: 11.5px;
    font-weight: 580;
}

.cfg-input,
.cfg-select,
.cfg-textarea {
    width: 100%;
    border: 1px solid #d7e2e9;
    border-radius: 8px;
    background: #fff;
    color: #263d32;
    font: inherit;
    font-size: 12.5px;
    outline: none;
}

.cfg-optional {
    margin-left: 4px;
    color: #70837a;
    font-size: 10px;
    font-weight: 500;
}

.cfg-uppercase { text-transform: uppercase; }

.cfg-input,
.cfg-select {
    height: 37px;
    padding: 0 9px;
}

.cfg-password-wrap { position: relative; }
.cfg-password-hint { display: block; margin-top: 5px; color: var(--muted, #64748b); font-size: 12px; line-height: 1.45; }
.cfg-password-wrap .cfg-input { padding-right: 42px; }
.cfg-password-toggle {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    padding: 2px;
    border: 0;
    background: transparent;
    color: #52675d;
    font-size: 17px;
    line-height: 1;
    cursor: pointer;
}

.cfg-textarea {
    min-height: 65px;
    padding: 8px 9px;
    resize: vertical;
}

.cfg-input:focus,
.cfg-select:focus,
.cfg-textarea:focus {
    border-color: #17824f;
    box-shadow: 0 0 0 3px rgba(23, 130, 79, .08);
}

.cfg-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.cfg-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 35px;
    padding: 7px 12px;
    border: 1px solid #17824f;
    border-radius: 8px;
    background: #17824f;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.cfg-btn.secondary {
    border-color: #d6e1e8;
    background: #fff;
    color: #294867;
}

.cfg-btn.danger {
    border-color: #e0a6a6;
    background: #fff;
    color: #a11b1b;
}

.cfg-table-wrap {
    overflow: auto;
    border: 1px solid #e6edf1;
    border-radius: 10px;
}

.cfg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.cfg-table th,
.cfg-table td {
    padding: 9px 8px;
    border-bottom: 1px solid #edf1f4;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap;
}

.cfg-table th {
    background: #f5f8f6;
    color: #2d4b67;
    font-weight: 600;
    letter-spacing: .25px;
}

.cfg-table tr:last-child td { border-bottom: 0; }

.cfg-row-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.cfg-status {
    display: inline-flex;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 600;
}

.cfg-status.active {
    background: #dcfce7;
    color: #166534;
}

.cfg-status.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.cfg-edit-row td {
    background: #fbfdfc;
}

.cfg-edit-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(130px, 1fr));
    gap: 8px;
    align-items: end;
}

.cfg-pagination {
    margin-top: 10px;
}

.cfg-pagination svg {
    width: 16px !important;
    height: 16px !important;
    max-width: 16px !important;
    max-height: 16px !important;
}

.cfg-pagination nav { display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:11px;color:#6e7f76; }
.cfg-pagination nav > div { display:flex;align-items:center;gap:6px; }
.cfg-pagination nav a,
.cfg-pagination nav span { line-height:1; }
.cfg-pagination nav a { color:#176f45;text-decoration:none; }
.cfg-pagination nav [aria-current="page"] span { display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;border-radius:7px;background:#17824f;color:#fff; }

.cfg-empty {
    padding: 20px;
    text-align: center;
    color: #718096;
}

@media (max-width: 1050px) {
    .cfg-form,
    .cfg-edit-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 680px) {
    .cfg-page { padding: 12px; }
    .cfg-section-head { align-items: flex-start; flex-direction: column; }
    .cfg-form,
    .cfg-edit-form { grid-template-columns: 1fr; }
    .cfg-field.wide,
    .cfg-field.full { grid-column: auto; }
}

/* Solo contraste: no altera medidas ni comportamiento de Configuración. */
html[data-theme="dark"] .cfg-page { color:#e5f0eb; }
html[data-theme="dark"] .cfg-header h1,
html[data-theme="dark"] .cfg-section-head h2 { color:#f2faf6; }
html[data-theme="dark"] .cfg-header p,
html[data-theme="dark"] .cfg-section-head small,
html[data-theme="dark"] .cfg-empty { color:#b7cbc1; }
html[data-theme="dark"] .cfg-nav a { background:#202d32; border-color:#405158; color:#dce9e3; }
html[data-theme="dark"] .cfg-nav a.active { background:#214638; border-color:#42b77d; color:#8ce4b8; }
html[data-theme="dark"] .cfg-section { background:#192429; border-color:#405159; box-shadow:none; }
html[data-theme="dark"] .cfg-create { background:#202f34; border-color:#405159; }
html[data-theme="dark"] .cfg-create summary { background:#21332d; color:#73dda9; }
html[data-theme="dark"] .cfg-field label { color:#d6e7df!important; }
html[data-theme="dark"] .cfg-field input,
html[data-theme="dark"] .cfg-field select,
html[data-theme="dark"] .cfg-field textarea { background:#111c20!important; border-color:#4a5f67!important; color:#f5faf8!important; }
html[data-theme="dark"] .cfg-table-wrap { border-color:#405159; }
html[data-theme="dark"] .cfg-table th { background:#25343a; color:#dff0e7; border-color:#465960; }
html[data-theme="dark"] .cfg-table td { background:#1d292e; color:#edf6f2; border-color:#3d5057; }
html[data-theme="dark"] .cfg-edit-row td { background:#223137; }
html[data-theme="dark"] .cfg-btn.secondary { background:#304047; color:#edf6f2; border-color:#566970; }
html[data-theme="dark"] .cfg-btn.danger { background:#3a2628; color:#ffb7b7; border-color:#754247; }
html[data-theme="dark"] .cfg-pagination nav { color:#b5c9bf; }
.cfg-edit-row[hidden],.cfg-edit-dialog button[hidden]{display:none!important}
.cfg-edit-dialog,.cfg-edit-dialog *{box-sizing:border-box}
.cfg-edit-dialog{position:fixed;inset:0;margin:auto;width:min(760px,calc(100vw - 40px));height:fit-content;max-height:calc(100dvh - 48px);padding:0;border:1px solid #d7e4dc;border-radius:16px;background:#fff;color:#263d32;box-shadow:0 24px 80px #071e1840;overflow:hidden}
.cfg-edit-dialog[open]{display:flex;flex-direction:column}
.cfg-edit-dialog::backdrop{background:#0b201a85;backdrop-filter:blur(3px)}
.cfg-edit-dialog-head{display:flex;flex-shrink:0;align-items:flex-start;justify-content:space-between;gap:20px;padding:20px 24px;border-bottom:1px solid #e1eae5}
.cfg-edit-dialog-head h2{margin:5px 0 6px;font-size:23px;color:#173c2a;font-weight:650}.cfg-edit-dialog-head p{margin:0;font-size:13px;color:#718096}
.cfg-dialog-close{display:grid;place-items:center;flex:none;width:34px;height:34px;border:1px solid #dce7e0;border-radius:8px;background:#f5f9f6;color:#496052;font-size:25px;cursor:pointer}
.cfg-edit-dialog-content{padding:20px 24px;overflow:auto;min-height:0;overscroll-behavior:contain}
.cfg-edit-dialog .cfg-edit-form{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px;align-items:start}
.cfg-edit-dialog .cfg-field{gap:7px}.cfg-edit-dialog .cfg-field label{font-size:13px;line-height:1.45}
.cfg-edit-dialog :is(.cfg-input,.cfg-select){height:42px;font-size:14px}.cfg-edit-dialog .cfg-field.wide{grid-column:1/-1}
.cfg-edit-dialog .cfg-field:has(>select[name="role"]){grid-column:1/-1}
.cfg-edit-dialog .cfg-edit-form>.cfg-field:has([data-report-consultation]){padding-top:8px;border-top:1px solid #e1eae5}
.cfg-edit-dialog .cfg-password-hint{font-size:11px;margin-top:0}
.cfg-edit-dialog-actions{display:flex;flex-shrink:0;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid #e1eae5;background:#f6faf7}
.cfg-edit-dialog-actions .cfg-btn{min-height:40px;font-size:13px;padding:9px 16px}
.cfg-edit-dialog :is(input,select,textarea,button):focus-visible{outline:2px solid #16835588;outline-offset:2px}
html[data-theme="dark"] .cfg-edit-dialog{background:#1d292e;color:#edf6f2;border-color:#405159}
html[data-theme="dark"] .cfg-edit-dialog-head h2{color:#e3f1e9}html[data-theme="dark"] .cfg-edit-dialog-head p{color:#b0c6bb}
html[data-theme="dark"] .cfg-edit-dialog-head,html[data-theme="dark"] .cfg-edit-dialog-actions,html[data-theme="dark"] .cfg-edit-dialog .cfg-edit-form>.cfg-field:has([data-report-consultation]){border-color:#405159}
html[data-theme="dark"] .cfg-edit-dialog-actions,html[data-theme="dark"] .cfg-dialog-close{background:#23363a;color:#d5e9df;border-color:#405159}
@media(max-width:600px){.cfg-edit-dialog .cfg-edit-form{grid-template-columns:minmax(0,1fr)}.cfg-edit-dialog-head,.cfg-edit-dialog-content{padding:18px}.cfg-edit-dialog-actions{padding:14px 18px;flex-wrap:wrap}}
</style>
