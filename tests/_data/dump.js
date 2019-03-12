db = db.getSiblingDB('lto_workflow_tests');

/*
 * Create MongoDB elements needed for the API tests
 */

db.getCollection("scenarios").insert([
    {
        "_id": "2557288f-108e-4398-8d2d-7914ffd93150",
        "schema": "https://specs.livecontracts.io/v0.2.0/scenario/schema.json#",
        "title": "Basic system and user",
        "actors": [
            {
                "key": "user",
                "title": "User"
            },
            {
                "key": "system",
                "title": "System",
                "signkeys": [
                    "57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn"
                ],
                "identity": "6uk7288s-afe4-7398-8dbh-7914ffd930pl"
            }
        ],
        "actions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/action/http/schema.json#",
                "key": "step1",
                "title": "Step1",
                "description": "Step1",
                "label": "Launch step 1",
                "actors": ["system"],
                "url": "https://www.example.com",
                "responses": {
                    "ok": { },
                    "error": { }
                }
            },
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/action/nop/schema.json#",
                "key": "step2",
                "title": "Step2",
                "description": "Step2",
                "label": "Launch step 2",
                "trigger_response": "ok",
                "data": "second response",
                "actors": ["system", "user"],
                "responses": {
                    "ok": { },
                    "error": { }
                }
            },
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/action/schema.json#",
                "key": "step3",
                "title": "Step3",
                "description": "Step3",
                "label": "Launch step 3",
                "actors": ["user"],
                "responses": {
                    "ok": { },
                    "cancel": { }
                }
            }
        ],
        "states": [
            {
                "key": ":initial",
                "actions": ["step1"],
                "title": "Initial state",
                "description": "Initial state",
                "instructions": [],
                "timeout": "P1D",
                "transitions": [
                    {
                        "action": "step1",
                        "response": "ok",
                        "transition": "second"
                    },
                    {
                        "action": "step1",
                        "response": "error",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "second",
                "actions": ["step2"],
                "title": "Second state",
                "description": "Second state",
                "instructions": [],
                "timeout": "P1D",
                "transitions": [
                    {
                        "action": "step2",
                        "response": "ok",
                        "transition": "third"
                    },
                    {
                        "action": "step2",
                        "response": "error",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "third",
                "actions": ["step3"],
                "title": "Third state",
                "description": "Third state",
                "instructions": [],
                "timeout": "P1D",
                "transitions": [
                    {
                        "transition": ":success"
                    }
                ]
            }
        ]
    }
]);

db.getCollection("processes").insert([
    {
        "_id": "4527288f-108e-fk69-8d2d-7914ffd93894",
        "schema": "https://specs.livecontracts.io/v0.2.0/process/schema.json#",           
        "title": "Basic system and user",
        "scenario": "2557288f-108e-4398-8d2d-7914ffd93150",
        "actors": [
            {
                "key": "user",
                "title": "User"
            },
            {
                "key": "system",
                "title": "System",
                "signkeys": [
                    "57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn"
                ],
                "identity": "6uk7288s-afe4-7398-8dbh-7914ffd930pl"
            }
        ],
        "current": {
            "key": ":initial",
            "actions": [
                {
                    "schema": "https://specs.livecontracts.io/v0.2.0/action/http/schema.json#",
                    "key": "step1",
                    "title": "Step1",
                    "actor": "system",
                    "url": "https://www.example.com",
                    "responses": {
                        "ok": { },
                        "error": { }
                    }
                }
            ],
            "transitions": [
                {
                    "action": "step1",
                    "response": "ok",
                    "transition": "second"
                },
                {
                    "action": "step1",
                    "response": "error",
                    "transition": ":failed"
                }
            ]
        }        
    }
]);

db.getCollection("identities").insert([
    {
        "_id": "1237288f-8u6f-3edt-8d2d-4f4ffd938vk",
        "node" : "amqps://localhost",
        "signkeys" : {
            "user" : "57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn",
            "system" : "FkU1XyfrCftc4pQKXCrrDyRLSnifX1SMvmx1CYiiyB3Y"
        },
        "encryptkey" : "9fSos8krst114LtaYGHQPjC3h1CQEHUQWEkYdbykrhHv"
    }
]);
