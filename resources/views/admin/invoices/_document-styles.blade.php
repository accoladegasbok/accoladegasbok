{{-- FILE: resources/views/admin/invoices/_document-styles.blade.php
     One stylesheet for the invoice/receipt BODY. Included by show.blade.php (screen + print)
     and invoice-pdf.blade.php (PDF), so both render from the same markup and the same CSS.
     Written for DomPDF as well as browsers: table layout only, no flex/grid, no emoji. --}}
.doc { font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.35; }
.doc table { width: 100%; border-collapse: collapse; }
.doc td, .doc th { vertical-align: top; }
.doc .doc-brand { font-size: 22px; font-weight: bold; color: #0d1b2a; letter-spacing: 0.5px; }
.doc .doc-brand span { color: #c9a84c; }
.doc .doc-tagline { font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
.doc .doc-company { font-size: 11px; font-weight: bold; margin-top: 6px; }
.doc .doc-small { font-size: 10px; color: #444; }
.doc .doc-title { font-size: 20px; font-weight: bold; text-align: right; color: #0d1b2a; }
.doc .doc-meta td { font-size: 10px; padding: 1px 0; }
.doc .doc-meta td.k { color: #888; text-align: right; padding-right: 8px; }
.doc .doc-meta td.v { font-weight: bold; text-align: right; }
.doc .doc-stamp { display: inline-block; border: 2px solid #1b9e5c; color: #1b9e5c; font-weight: bold; font-size: 13px; letter-spacing: 2px; padding: 2px 10px; margin-top: 4px; }
.doc .doc-stamp.partial { border-color: #e65100; color: #e65100; }
.doc .doc-stamp.unpaid  { border-color: #c0392b; color: #c0392b; }
.doc .doc-rev { border: 1px solid #a32d2d; color: #a32d2d; font-size: 9px; font-weight: bold; padding: 1px 5px; margin-left: 4px; }
.doc .doc-box { border: 1px solid #ddd; padding: 7px 9px; }
.doc .doc-box h4 { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin: 0 0 4px 0; font-weight: bold; }
.doc .doc-box .nm { font-size: 12px; font-weight: bold; }
.doc .doc-gate th { background: #fdecea; color: #b71c1c; text-align: left; font-size: 11px; padding: 5px 8px; border: 1px solid #f5b3ab; }
.doc .doc-gate td { border: 1px solid #f5b3ab; padding: 5px 8px; font-size: 10px; }
.doc .doc-gate td .lbl { display: block; font-size: 8px; text-transform: uppercase; color: #999; }
.doc .doc-items { margin-top: 12px; }
.doc .doc-items th { background: #0d1b2a; color: #ffffff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 6px; text-align: left; }
.doc .doc-items td { padding: 6px; border-bottom: 1px solid #e6e6e6; font-size: 10.5px; }
.doc .doc-items .r { text-align: right; }
.doc .doc-items .c { text-align: center; }
.doc .doc-name { font-size: 11.5px; font-weight: bold; }
.doc .doc-ids { font-size: 10px; color: #0d1b2a; margin-top: 2px; }
.doc .doc-ids strong { font-family: 'DejaVu Sans Mono', monospace; }
.doc .doc-muted { font-size: 9px; color: #777; margin-top: 1px; }
.doc .doc-flag { font-size: 9px; font-weight: bold; padding: 0 4px; border: 1px solid; }
.doc .doc-flag.ret { color: #a32d2d; border-color: #a32d2d; }
.doc .doc-disc { color: #e65100; font-weight: bold; }
.doc .doc-grade { font-weight: bold; }
.doc .doc-totals td { padding: 3px 6px; font-size: 11px; }
.doc .doc-totals td.r { text-align: right; }
.doc .doc-totals tr.grand td { background: #0d1b2a; color: #ffffff; font-weight: bold; font-size: 12.5px; }
.doc .doc-totals tr.paid td { border-top: 1px dashed #bbb; color: #1b9e5c; font-weight: bold; }
.doc .doc-totals tr.due td { font-weight: bold; font-size: 12.5px; border-top: 1px solid #ccc; }
.doc .doc-totals tr.sub td { color: #555; font-size: 10px; }
.doc .doc-waybill { border: 2px dashed #8a6d1f; padding: 8px 12px; background: #fffbf0; margin-top: 10px; font-size: 10px; color: #5d4e1f; }
.doc .doc-warranty { margin-top: 12px; background: #fffbe6; border: 1px solid #e6d68a; padding: 7px 9px; font-size: 8.5px; color: #6b5516; line-height: 1.45; }
.doc .doc-warranty.asis { background: #fdecea; border-color: #f5b3ab; color: #7a1d14; }
.doc .doc-sig td { width: 33%; padding: 22px 10px 0 10px; text-align: center; font-size: 9px; color: #555; }
.doc .doc-sig .line { border-top: 1px solid #333; padding-top: 3px; }
.doc .doc-foot { margin-top: 12px; padding-top: 6px; border-top: 1px solid #ccc; text-align: center; font-size: 9px; color: #444; }
.doc .doc-foot td { font-size: 9px; text-align: left; padding: 2px 8px; }
