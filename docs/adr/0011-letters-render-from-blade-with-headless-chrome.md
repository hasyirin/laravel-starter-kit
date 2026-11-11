# Letters Render From Blade With Headless Chrome

The kit makes each letter as a Blade view and turns it into a PDF with headless Chrome. It uses the Spatie Laravel PDF package and its Browsershot driver. Chrome supports current CSS, so a letter uses the app's own Tailwind build. The package hides the engine, so an app can change to another driver in config.

## Considered Options

- **wkhtmltopdf through Snappy**: rejected. Earlier apps used it. The project is archived, and it has an unfixed SSRF flaw (CVE-2022-35583). Its old WebKit has no flexbox or grid, so each view needed CSS made by Mailwind.
- **dompdf**: rejected as the default. It needs no binary, but its CSS support is weaker than that of wkhtmltopdf.
- **Word templates converted by LibreOffice**: rejected for the kit. They need LibreOffice on each server and a second template language next to Blade. An app that must give staff an editable Word file can add this itself.

## Consequences

- Each server needs Node, Puppeteer, and Chrome. A render takes about one to two seconds, so a large batch must run in a queue.
- Headers, footers, and page numbers use CSS page margin boxes, which need Chrome 131 or later. The `@page :first` rule gives the first page its own header and footer, so no PDF tool has to add them after the render.
