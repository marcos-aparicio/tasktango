<?php
return [
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => 'file|mimes:png,jpg,pdf,doc,docx|max:102400',  // (100MB max, and only pngs, jpegs, and pdfs.)
    ],
];
