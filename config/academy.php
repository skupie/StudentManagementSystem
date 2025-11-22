<?php

return [
    'classes' => [
        'hsc_1' => 'HSC 1st Year',
        'hsc_2' => 'HSC 2nd Year',
    ],
    'sections' => [
        'science' => 'Science',
        'humanities' => 'Humanities',
        'business_studies' => 'Business Studies',
    ],
    'subjects' => [
        'common' => [
            'bangla_1st' => 'Bangla 1st',
            'bangla_2nd' => 'Bangla 2nd',
            'english_1st' => 'English 1st',
            'english_2nd' => 'English 2nd',
            'ict' => 'ICT',
        ],
        'by_section' => [
            'science' => [
                'physics_1st' => 'Physics 1st',
                'physics_2nd' => 'Physics 2nd',
                'chemistry_1st' => 'Chemistry 1st',
                'chemistry_2nd' => 'Chemistry 2nd',
                'math_1st' => 'Math 1st',
                'math_2nd' => 'Math 2nd',
                'botany' => 'Botany',
                'zoology' => 'Zoology',
            ],
            'business_studies' => [
                'accounting_1st' => 'Accounting 1st',
                'accounting_2nd' => 'Accounting 2nd',
                'economics_1st' => 'Economics 1st',
                'economics_2nd' => 'Economics 2nd',
                'finance_1st' => 'Finance 1st',
                'finance_2nd' => 'Finance 2nd',
                'business_management_1st' => 'Business Management 1st',
                'business_management_2nd' => 'Business Management 2nd',
                'production_management_1st' => 'Production Management 1st',
                'production_management_2nd' => 'Production Management 2nd',
            ],
            'humanities' => [
                'social_work_1st' => 'Social Work 1st',
                'social_work_2nd' => 'Social Work 2nd',
                'civics_1st' => 'Civics 1st',
                'civics_2nd' => 'Civics 2nd',
                'economics_1st' => 'Economics 1st',
                'economics_2nd' => 'Economics 2nd',
                'islamic_history_1st' => 'Islamic History 1st',
                'islamic_history_2nd' => 'Islamic History 2nd',
                'geography_1st' => 'Geography 1st',
                'geography_2nd' => 'Geography 2nd',
                'history_1st' => 'History 1st',
                'history_2nd' => 'History 2nd',
                'islamic_study_1st' => 'Islamic Study 1st',
                'islamic_study_2nd' => 'Islamic Study 2nd',
            ],
        ],
    ],
    'absence_categories' => [
        'Sick',
        'Personal',
        'Family',
        'Other',
    ],
    'payment_modes' => [
        'Cash',
        'Bkash',
        'Nagad',
        'Bank Transfer',
    ],
    'expense_categories' => [
        'Supplies',
        'Salaries',
        'Rent',
        'Utilities',
        'Miscellaneous',
    ],
    'full_payment_exceptions' => [
        // Add student IDs here for always-full payment handling
    ],
];
