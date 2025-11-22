<?php

return [
    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
    'rpc_url' => env('BLOCKCHAIN_RPC_URL'),

    // ABI yang sudah dikonversi ke format Array PHP
    'abi' => [
        // 1. Event: DonationRecorded
        [
            "anonymous" => false,
            "inputs" => [
                [
                    "indexed" => true,
                    "internalType" => "uint256",
                    "name" => "laravelId",
                    "type" => "uint256"
                ],
                [
                    "indexed" => false,
                    "internalType" => "uint256",
                    "name" => "amountInUSD",
                    "type" => "uint256"
                ],
                [
                    "indexed" => false,
                    "internalType" => "address",
                    "name" => "donorWallet",
                    "type" => "address"
                ],
                [
                    "indexed" => false,
                    "internalType" => "uint256",
                    "name" => "timestamp",
                    "type" => "uint256"
                ]
            ],
            "name" => "DonationRecorded",
            "type" => "event"
        ],

        // 2. Function: donationRecords (View)
        [
            "inputs" => [
                [
                    "internalType" => "uint256",
                    "name" => "",
                    "type" => "uint256"
                ]
            ],
            "name" => "donationRecords",
            "outputs" => [
                [
                    "internalType" => "uint256",
                    "name" => "laravelId",
                    "type" => "uint256"
                ],
                [
                    "internalType" => "uint256",
                    "name" => "amountInUSD",
                    "type" => "uint256"
                ],
                [
                    "internalType" => "address",
                    "name" => "donorWallet",
                    "type" => "address"
                ],
                [
                    "internalType" => "uint256",
                    "name" => "timestamp",
                    "type" => "uint256"
                ]
            ],
            "stateMutability" => "view",
            "type" => "function"
        ],

        // 3. Function: getDonationRecord (View)
        [
            "inputs" => [
                [
                    "internalType" => "uint256",
                    "name" => "_index",
                    "type" => "uint256"
                ]
            ],
            "name" => "getDonationRecord",
            "outputs" => [
                [
                    "internalType" => "uint256",
                    "name" => "",
                    "type" => "uint256"
                ],
                [
                    "internalType" => "uint256",
                    "name" => "",
                    "type" => "uint256"
                ],
                [
                    "internalType" => "address",
                    "name" => "",
                    "type" => "address"
                ],
                [
                    "internalType" => "uint256",
                    "name" => "",
                    "type" => "uint256"
                ]
            ],
            "stateMutability" => "view",
            "type" => "function"
        ],

        // 4. Function: recordDonation (Write)
        [
            "inputs" => [
                [
                    "internalType" => "uint256",
                    "name" => "_laravelId",
                    "type" => "uint256"
                ],
                [
                    "internalType" => "uint256",
                    "name" => "_amount",
                    "type" => "uint256"
                ]
            ],
            "name" => "recordDonation",
            "outputs" => [],
            "stateMutability" => "nonpayable",
            "type" => "function"
        ]
    ],
];