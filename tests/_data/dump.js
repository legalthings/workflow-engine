db = db.getSiblingDB('lto_workflow_tests');

/*
 * Create MongoDB elements needed for the API tests
 */

db.getCollection("scenarios").insert([
    {
        "_id": "2557288f-108e-4398-8d2d-7914ffd93150",
        "schema": "https://specs.livecontracts.io/v1.0.0/scenario/schema.json#",
        "actors": [
            {
                "key": "user",
                "title": "User"
            },
            {
                "key": "system",
                "title": "System"
            }
        ],
        "actions": [
            {
                "schema": "https://specs.livecontracts.io/v1.0.0/action/http/schema.json#",
                "key": "step1",
                "title": "Step1",
                "actor": "system",
                "url": "https://www.example.com",
                "responses": {
                    "ok": { },
                    "error": { }
                }
            },
            {
                "schema": "https://specs.livecontracts.io/v1.0.0/action/nop/schema.json#",
                "key": "step2",
                "title": "Step2",
                "trigger_response": "ok",
                "data": "second response",
                "actor": "system",
                "responses": {
                    "ok": { },
                    "error": { }
                }
            },
            {
                "schema": "https://specs.livecontracts.io/v1.0.0/action/schema.json#",
                "key": "step3",
                "title": "Step3",
                "actor": "user",
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
                "transitions": [
                    {
                        "action": "step1",
                        "response": "ok",
                        "transition": "step2"
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
                "transitions": [
                    {
                        "action": "step2",
                        "response": "ok",
                        "transition": "step3"
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
]);
