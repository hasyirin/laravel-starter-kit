# Media Files Follow the Database Transaction

The package spatie/laravel-medialibrary writes and deletes the files of a media at once, also inside a transaction. When the transaction rolled back, a new media left its files on disk with no row. A deleted media came back as a row, but its files were gone. This also happened when a single-file collection replaced its old media, such as the header image of a letterhead. Now the kit's `MediaObserver`, set in `media-library.media_observer`, ties the files to the transaction. A rollback removes the files of each new media. The files of a deleted media leave the disk only after the commit. With no open transaction, they leave at once, as before.

## Considered Options

- **A rollback callback in each Action class**: rejected. `StoresLetterImages` did this for letter images. It misses the media that the package deletes itself, such as the old media of a single-file collection. Also, each Action class of each app must remember it.
- **No single-file collections, so that `SyncMedia` deletes the old media after the commit**: rejected. Each caller must then limit the number of files, and a direct `addMedia()` can give a letterhead two header images. Every other delete inside a transaction stays unsafe, such as a `clearMediaCollection()` call in an app's Action class.

## Consequences

- If the files of a deleted media fail to leave the disk after the commit, the kit reports the error and does not throw. The change stays saved, and the files stay on disk.
- Spatie still renames the files at once when a media's `file_name` changes. The kit never changes it.
- The kit extends the package's `MediaObserver`, and its `deleted()` replaces the package's `deleted()`. After a package update, compare the two methods. The tests in `tests/Feature/Domain/Shared/Media/Observers/MediaObserverTest.php` cover only today's behavior.
- An app that sets its own `media_observer` must extend the kit's observer, or it loses this rule.
