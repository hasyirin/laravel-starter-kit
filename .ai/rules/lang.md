---
paths:
  - 'lang/**'
---

# Lang

## Never use em dashes
Never write an em dash (`—` or `&mdash;`) in the frontend. Rewrite the sentence, or use a comma, colon, period, or parentheses instead.

## Cut secondary text that adds no information
Add a description, hint, or secondary label only when it tells the user something the heading and controls do not. Examples are a reason, a consequence, a next step, or what a feature does. Cut text that only repeats the heading. If a screen looks bare without it, fix the layout. If you are not sure, ask the user.

## Write Malay in a formal tone, and keep IT terms in English
Write the Malay text in formal Malaysian usage: "anda", "Sila", full sentences, no slang, and no short forms. Use the `_Malay_` term of `GLOSSARY.md` for each glossary term, and never a synonym.

Use the Malay word when a Malaysian government system shows it on its sign-in and form pages. Keep the English word, without italics, when the Malay form is a coinage that people do not say. Keep product and protocol names as they are.

| Group | English | Malay text |
| --- | --- | --- |
| Everyday words | password, log in, log out, register, email, upload, download, save, update, delete, cancel, back, search, settings | Kata laluan, Log masuk, Log keluar, Daftar, E-mel, Muat naik, Muat turun, Simpan, Kemas kini, Padam, Batal, Kembali, Cari, Tetapan |
| Everyday words | recovery code, two-factor authentication, notification, audit log | Kod pemulihan, Pengesahan dua faktor, Notifikasi, Log audit |
| IT terms | dashboard, firewall, passkey, browser, cookie, cache, token, server, queue, log | The same word |
| Names | Horizon, SMS, 2FA, PDF, URL | The same name |

Never write "lalai" or "tembok api". For "default", write the sentence in another way, for example "Kepala surat utama". Write the word "default" only when no other wording works.

If a word fits no group, ask the user.

## Add each key to every language
Add a new key to `lang/en` and to the same file of every other language, such as `lang/ms`, in the same change. Keep the placeholders, such as `:name`, the same in each language. `tests/Feature/Lang/ParityTest.php` fails when a key is in one language only. Put only the English sentences that packages use as keys in a JSON file, such as `lang/ms.json`. See `docs/localization.md`.
