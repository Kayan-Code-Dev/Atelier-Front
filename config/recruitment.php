<?php

return [
    'cv_max_kilobytes' => 5120,
    'portfolio_max_kilobytes' => 8192,
    'disk' => 'local',
    'cv_directory' => 'recruitment/cvs',
    'allowed_cv_extensions' => ['pdf', 'doc', 'docx'],
    'allowed_cv_mimes' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-office',
    ],
    'job_statuses' => ['draft', 'published', 'closed', 'archived'],
    'application_statuses' => [
        'new',
        'screening',
        'shortlisted',
        'interview',
        'final_review',
        'accepted',
        'rejected',
    ],
    'employment_types' => ['full_time', 'part_time', 'contract', 'internship'],
];
