<?php

return [
    [
        "group" => "礼拜组",
        "color" => "#CD5C5C",
        "permission" => "planer_worship",
        "roles" => [
            [
                "role" => "主题",
                "name" => "主题",
                'type' => 'input',
                'placeholder' => '主题+经文，请使用+号分隔',
            ],
            [
                "role" => "证道",
                "name" => "证道",
                'type' => 'select',
                'max' => 1,
            ],
            [
                "role" => "司会",
                "name" => "司会",
                'type' => 'select',
                'max' => 1,
            ],
            [
                "role" => "司琴",
                "name" => "司琴",
                'type' => 'select',
                'max' => 1,
            ],
            [
                "role" => "领唱",
                "name" => "领唱小组",
                'type' => 'select',
                'max' => 2,
            ],
            [
                "role" => "主执",
                "name" => "主领执事",
                'type' => 'select',
                'max' => 1,
            ],
            [
                "role" => "音响",
                "name" => "音响",
                'type' => 'select',
                'max' => 1,
            ],
            [
                "role" => "接待收拾",
                "name" => "接待\n会后收拾",
                'type' => 'select',
                'max' => 2,
            ]
        ]
    ],
    [
        "group" => "教育组",
        "color" => "#008000",
        "permission" => "planer_education",
        "roles" => [
            [
                "role" => "少年班",
                "name" => "少年主日学",
                'type' => 'select',
                'max' => 2,
            ],
            [
                "role" => "少儿班",
                "name" => "少儿主日学",
                'type' => 'select',
                'max' => 3,
            ],
            [
                "role" => "幼儿班",
                "name" => "幼儿主日学",
                'type' => 'select',
                'max' => 3,
            ],
        ]
    ],
    [
        "group" => "图书组",
        "color" => "#BA55D3",
        "permission" => "planer_book",
        "roles" => [
            [
                "role" => "PPT制作播放",
                "name" => "PPT制作播放",
                'type' => 'select',
                'max' => 2,
            ],
        ]
    ],
    [
        "group" => "服务组",
        "color" => "#4682B4",
        "permission" => "planer_service",
        "roles" => [
            [
                "role" => "餐前准备",
                "name" => "餐前准备",
                'type' => 'select',
                'max' => 1,
            ],
            [
                "role" => "主厨",
                "name" => "主厨",
                'type' => 'select',
                'max' => 2,
            ],
            [
                "role" => "餐后洗碗",
                "name" => "准备\n收拾\n分餐",
                'type' => 'select',
                'max' => 3,
            ],
        ]
    ],
    [
        "group" => "管堂组",
        "color" => "#A0522D",
        "permission" => "planer_management",
        "roles" => [
            [
                "role" => "管堂组",
                "name" => "管堂小组\n（预备）",
                'type' => 'select',
                'max' => 2,
            ],
        ]
    ],
];

