# Letters Bundle Their Fonts in One Folder for Each Family

Each letter PDF uses only fonts that the kit bundles. The folder `resources/fonts/letter` holds one folder for each font family, with static WOFF2 files that have fixed names such as regular.woff2 and bold.woff2. The kit bundles Inter as the family Letter Sans and JetBrains Mono as the family Letter Mono, both with the SIL Open Font License. `resources/css/letter.css` has one `@font-face` rule for each file, and the build writes each file into the stylesheet as a `data:` URI. An app replaces a font when it overwrites the files of a family and runs the build. An app adds a family with a new folder, its `@font-face` rules, one `@theme` line, and a line in the font fixture and the font test.

Before this, a letter used the system font of the machine that made the PDF. The same letter looked different on macOS, on a Linux server, and on CI. On macOS, Chrome embedded the system font as a Type 3 font, and `pdftotext` then reported wrong word positions, so a layout test failed.

## Considered Options

- **Bundle Microsoft Aptos**: rejected. Aptos was the intended font. The Microsoft font redistribution FAQ does not allow a copy of a font on a server, a conversion to WOFF2, or redistribution, and this repository is public. The FAQ covers the fonts of Windows, and the exact terms of Aptos were not read. The Aptos page of Microsoft points to a paid license for server use.
- **Read the font from a path that each deployment supplies**: rejected as the default. A letter would still look different where the file is absent, and the tests could not name the font. An app with a font license gets the same result when it replaces the files.
- **A variable font file**: rejected. The macOS system font is a variable font, and Chrome embedded it as a Type 3 font. Static files with TrueType outlines become TrueType fonts in the PDF. A static file with CFF outlines, as in most OTF files, also becomes a Type 3 font.
- **The full font files of each release**: rejected. They also hold Greek and Cyrillic, but they make each letter page about 850 KB. The subsets keep the page at about 350 KB.
- **Name the families Inter and JetBrains Mono in the stylesheet**: rejected. An app that replaces a font would have to change the stylesheet too. A name for the role, such as Letter Sans, stays correct for any font.
- **Make the `@font-face` rules from the file names with a script**: rejected. It saves a few lines for each family, but it hides how a font loads.
- **Load the font from a font service or a file URL**: rejected. The letter page allows no request. See [kit ADR-0011](0011-letters-render-from-blade-with-headless-chrome.md).

## Consequences

- Each letter page holds all the font files, about 350 KB together. The PDF holds only the fonts that the letter uses. A render does not take longer.
- Each new family makes every letter page larger, also for a letter that does not use the family.
- The files are subsets that hold the Latin and Latin Extended characters and common symbols. Chrome prints any other character in a system font, so each server still needs a font package. A font for another script, such as Jawi, is not bundled yet. `docs/modules/letters.md` holds the command that made the subsets.
- For a weight without a file, Chrome uses the nearest file and gives no error.
- A letter view can use the weights and styles that have a file. Another weight needs another font file and a `@font-face` rule.
- Two tests read the fonts of a letter PDF and name the bundled fonts. One accepts only the type CID TrueType. An app that replaces or adds a font changes the names in those tests. `tests/Pest.php` makes `composer test` run them again after a change to a font file or to the letter stylesheet.
- The subsets have no contextual alternates and no code ligatures, so a letter prints -> and != as typed. Mono text uses the sans file for a character that the mono file does not have.
- `vite.config.js` inlines every file in `resources/fonts/letter`, whatever its size, and no other file.
- The letter page holds one hidden character in the regular sans file. Without it, Chrome prints the page number in a system font when the body has no regular text.
- The stylesheet always has the classes `font-bold`, `italic`, and `font-mono`, also when no letter view uses them.
