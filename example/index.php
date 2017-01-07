<?php

require_once __DIR__ . '/vendor/autoload.php';

$tree = [
    'name'    => 'child',
    'parents' => [
        'mother' => [
            'name'    => 'mother',
            'parents' => [
                'mother' => [
                    'name' => 'first grand mother'
                ],
                'father' => [
                    'name' => 'first grand father'
                ]
            ]
        ],
        'father' => [
            'name'    => 'father',
            'parents' => [
                'mother' => [
                    'name' => 'second grand mother'
                ],
                'father' => [
                    'name'    => 'second grand father',
                    'parents' => [
                        'mother' => [
                            'name' => 'second grand grand mother'
                        ]
                    ]
                ]
            ]
        ]
    ]
];

$walker = new \RGilyov\ArrayTreeWalker($tree);

/*
 * To walk through tree structure use ->, to get value use ->get('name');
 */

echo $walker->parents->mother->parents->father->get('name') . '<br>'; // result: `first grand father`

/*
 * You can also get value like so
 */

echo $walker->parents->mother->parents->father['name'] . '<br>'; // result: `first grand father`

echo $walker->parents->mother->parents->father->name['name'] . '<br>'; // result: `first grand father`

echo $walker->parents->mother->parents->father->name->father['name'] . '<br>'; // result: null

/*
 * You may also specify node name, just pass it as the second the class's constructor parameter
 */

$walkerWithNodeName = new \RGilyov\ArrayTreeWalker($tree, 'parents');

echo $walkerWithNodeName->father->father->get('name') . '<br>'; // result: `second grand father`

echo $walkerWithNodeName->father->father->mother['name'] . '<br>'; // result: 'second grand grand mother'

echo $walkerWithNodeName->father->father->mother->mother['name'] . '<br>'; // result: null

/*
 * List of available methods:
 */

$walker->toArray();
$walker->count();
$walker->offsetExists('key');
$walker->offsetUnset('key');
$walker->offsetGet('key');
$walker->offsetSet('key', 'value');
