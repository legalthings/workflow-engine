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
                "key": "organization",
                "title": "Organization"
            }
        ],
        "actions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/action/http/schema.json#",
                "key": "step1",
                "title": "Step1",
                "description": "Step1",
                "label": "Launch step 1",
                "actors": ["organization"],
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
                "actors": ["organization", "user"],
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
                "key": "initial",
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
    },
    {
        "_id": "rt5yh683-108e-5673-8d2d-7914ffd23e5t",
        "schema": "https://specs.livecontracts.io/v0.2.0/scenario/schema.json#",
        "title": "Basic system and user with update instructions",
        "actors": [
            {
                "key": "user",
                "title": "User"
            },
            {
                "key": "organization",
                "title": "Organization"
            }
        ],
        "actions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/action/http/schema.json#",
                "key": "step1",
                "title": "Step1",
                "description": "Step1",
                "label": "Launch step 1",
                "actors": ["organization"],
                "url": "https://www.example.com",
                "responses": {
                    "ok": {
                        "update": [
                            {
                                "select": "foo"
                            },
                            {
                                "select": "baz", 
                                "patch": true
                            },
                            {
                                "select": "bar",
                                "patch": false
                            }
                        ]
                    },
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
                "actors": ["organization", "user"],
                "responses": {
                    "ok": {
                        "update": [
                            {
                                "select": "bar"
                            }
                        ]
                    },
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
                    "ok": {
                        "update": [
                            {
                                "select": "bar",
                                "projection": "{id: test}"
                            }
                        ]
                    },
                    "cancel": { }
                }
            }
        ],
        "states": [
            {
                "key": "initial",
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
    },
    {
        "_id": "5gh893dv-108e-4398-8d2d-7914ffd934g8",
        "schema": "https://specs.livecontracts.io/v0.2.0/scenario/schema.json#",
        "title": "Simple event trigger scenario",
        "actors": [
            {
                "key": "user",
                "title": "User"
            },
            {
                "key": "organization",
                "title": "Organization"
            }
        ],
        "definitions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/asset/schema.json#",
                "key": "foo_identity",
                "node": "localhost",
                "signkeys" : {
                    "user" : "foo",
                    "system" : "bar"
                }
            }
        ],
        "actions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/action/event/schema.json#",
                "actors": ["organization"],
                "key": "step1",
                "body": {
                    "\\u0024schema": "https://specs.livecontracts.io/v0.2.0/identity/schema.json#",
                    "key": {
                        "<ref>": "definitions.foo_identity.key"
                    },
                    "node": {
                        "<ref>": "definitions.foo_identity.node"
                    },
                    "signkeys": {
                        "<ref>": "definitions.foo_identity.signkeys"
                    }
                },
                "responses": {
                    "ok": {
                        "display": "always"
                    },
                    "error": {
                        "display": "always",
                        "title": "Failed to add foo identity"
                    }
                }
            }
        ],
        "states": [
            {
                "key": "initial",
                "actions": ["step1"],
                "title": "Initial state",
                "description": "Initial state",
                "instructions": [],
                "transitions": [
                    {
                        "action": "step1",
                        "response": "ok",
                        "transition": ":success"
                    },
                    {
                        "action": "step1",
                        "response": "error",
                        "transition": ":failed"
                    }
                ]
            }
        ]
    },
    {
        "_id": "rty6782c-108e-4398-8d2d-7914ffdqw4t7",
        "schema": "https://specs.livecontracts.io/v0.2.0/scenario/schema.json#",
        "title": "Event trigger with array of events",
        "actors": [
            {
                "key": "user",
                "title": "User"
            },
            {
                "key": "organization",
                "title": "Organization"
            }
        ],
        "definitions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/asset/schema.json#",
                "key": "foo_identity",
                "node": "localhost",
                "signkeys" : {
                    "user" : "foo",
                    "system" : "bar"
                }
            },
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/asset/schema.json#",
                "key": "bar_identity",
                "node": "localhost",
                "signkeys" : {
                    "user" : "foo_bar",
                    "system" : "bar_baz"
                },
                "encryptkey": "zoo"
            }
        ],
        "actions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/action/event/schema.json#",
                "actors": ["organization"],
                "key": "step1",
                "body": [
                    {
                        "\\u0024schema": "https://specs.livecontracts.io/v0.2.0/identity/schema.json#",
                        "key": {
                            "<ref>": "definitions.foo_identity.key"
                        },
                        "node": {
                            "<ref>": "definitions.foo_identity.node"
                        },
                        "signkeys": {
                            "<ref>": "definitions.foo_identity.signkeys"
                        }
                    },
                    {
                        "\\u0024schema": "https://specs.livecontracts.io/v0.2.0/identity/schema.json#",
                        "key": {
                            "<ref>": "definitions.bar_identity.key"
                        },
                        "node": {
                            "<ref>": "definitions.bar_identity.node"
                        },
                        "signkeys": {
                            "<ref>": "definitions.bar_identity.signkeys"
                        },
                        "encryptkey": {
                            "<ref>": "definitions.bar_identity.encryptkey"
                        }
                    }
                ],
                "responses": {
                    "ok": {
                        "display": "always"
                    },
                    "error": {
                        "display": "always",
                        "title": "Failed to add foo identity"
                    }
                }
            }
        ],
        "states": [
            {
                "key": "initial",
                "actions": ["step1"],
                "title": "Initial state",
                "description": "Initial state",
                "instructions": [],
                "transitions": [
                    {
                        "action": "step1",
                        "response": "ok",
                        "transition": ":success"
                    },
                    {
                        "action": "step1",
                        "response": "error",
                        "transition": ":failed"
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
                "title": "User",
                "identity": "e2d54eef-3748-4ceb-b723-23ff44a2512b"
            },
            {
                "key": "organization",
                "title": "Organization",
                "identity": "6uk7288s-afe4-7398-8dbh-7914ffd930pl"
            }
        ],
        "current": {
            "key": "initial",
            "actions": [
                {
                    "schema": "https://specs.livecontracts.io/v0.2.0/action/http/schema.json#",
                    "key": "step1",
                    "title": "Step1",
                    "actor": "organization",
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
    },
    {
        "_id": "98kgh356-108e-fk69-8d2d-7914ffddf45h",
        "schema": "https://specs.livecontracts.io/v0.2.0/process/schema.json#",           
        "title": "Simple event trigger",
        "scenario": "5gh893dv-108e-4398-8d2d-7914ffd934g8",
        "chain": "2c83KDmRCJwaKWky1jmtTRYmsgXAhmuDC8P12KpqqbrQKkY6UMECmKeZE5m8Rx",
        "actors": [
            {
                "key": "user",
                "title": "User",
                "identity": "e2d54eef-3748-4ceb-b723-23ff44a2512b"
            },
            {
                "key": "organization",
                "title": "Organization",
                "identity": "6uk7288s-afe4-7398-8dbh-7914ffd930pl"
            }
        ],
        "definitions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/asset/schema.json#",
                "key": "foo_identity",
                "node": "localhost",
                "signkeys" : {
                    "user" : "foo",
                    "system" : "bar"
                }
            }
        ],
        "current": {
            "key": "initial",
            "actions": [
                {
                    "schema": "https://specs.livecontracts.io/v0.2.0/action/event/schema.json#",
                    "actors": ["organization"],
                    "key": "step1",
                    "body": {
                        "\\u0024schema": "https://specs.livecontracts.io/v0.2.0/identity/schema.json#",
                        "key": {
                            "<ref>": "definitions.foo_identity.key"
                        },
                        "node": {
                            "<ref>": "definitions.foo_identity.node"
                        },
                        "signkeys": {
                            "<ref>": "definitions.foo_identity.signkeys"
                        }
                    },
                    "responses": {
                        "ok": {
                            "display": "always"
                        },
                        "error": {
                            "display": "always",
                            "title": "Failed to add foo identity"
                        }
                    }
                }
            ],
            "transitions": [
                {
                    "action": "step1",
                    "response": "ok",
                    "transition": ":success"
                },
                {
                    "action": "step1",
                    "response": "error",
                    "transition": ":failed"
                }
            ]
        }        
    },
    {
        "_id": "3e5c7uy5-108e-fk69-8d2d-7914ffd23w6u",
        "schema": "https://specs.livecontracts.io/v0.2.0/process/schema.json#",           
        "title": "Event trigger with array of events",
        "scenario": "rty6782c-108e-4398-8d2d-7914ffdqw4t7",
        "chain": "2c83KDmRCJwaKWky1jmtTRYmsgXAhmuDC8P12KpqqbrQKkY6UMECmKeZE5m8Rx",
        "actors": [
            {
                "key": "user",
                "title": "User",
                "identity": "e2d54eef-3748-4ceb-b723-23ff44a2512b"
            },
            {
                "key": "organization",
                "title": "Organization",
                "identity": "6uk7288s-afe4-7398-8dbh-7914ffd930pl"
            }
        ],
        "definitions": [
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/asset/schema.json#",
                "key": "foo_identity",
                "node": "localhost",
                "signkeys" : {
                    "user" : "foo",
                    "system" : "bar"
                }
            },
            {
                "schema": "https://specs.livecontracts.io/v0.2.0/asset/schema.json#",
                "key": "bar_identity",
                "node": "localhost",
                "signkeys" : {
                    "user" : "foo_bar",
                    "system" : "bar_baz"
                },
                "encryptkey": "zoo"
            }
        ],
        "current": {
            "key": "initial",
            "actions": [
                {
                    "schema": "https://specs.livecontracts.io/v0.2.0/action/event/schema.json#",
                    "actors": ["organization"],
                    "key": "step1",
                    "body": [
                        {
                            "\\u0024schema": "https://specs.livecontracts.io/v0.2.0/identity/schema.json#",
                            "key": {
                                "<ref>": "definitions.foo_identity.key"
                            },
                            "node": {
                                "<ref>": "definitions.foo_identity.node"
                            },
                            "signkeys": {
                                "<ref>": "definitions.foo_identity.signkeys"
                            }
                        },
                        {
                            "\\u0024schema": "https://specs.livecontracts.io/v0.2.0/identity/schema.json#",
                            "key": {
                                "<ref>": "definitions.bar_identity.key"
                            },
                            "node": {
                                "<ref>": "definitions.bar_identity.node"
                            },
                            "signkeys": {
                                "<ref>": "definitions.bar_identity.signkeys"
                            },
                            "encryptkey": {
                                "<ref>": "definitions.bar_identity.encryptkey"
                            }
                        }
                    ],
                    "responses": {
                        "ok": {
                            "display": "always"
                        },
                        "error": {
                            "display": "always",
                            "title": "Failed to add foo identity"
                        }
                    }
                }
            ],
            "transitions": [
                {
                    "action": "step1",
                    "response": "ok",
                    "transition": ":success"
                },
                {
                    "action": "step1",
                    "response": "error",
                    "transition": ":failed"
                }
            ]
        }        
    },
    {
        "_id": "cad2f7fd-8d1d-410d-8ae4-c60c0aaf05e4",
        "schema": "https://specs.livecontracts.io/v0.2.0/process/schema.json#",
        "title": "Basic system and user",
        "scenario": "2557288f-108e-4398-8d2d-7914ffd93150",
        "chain": "2c83KDmRCJwaKWky1jmtTRYmsgXAhmuDC8P12KpqqbrQKkY6UMECmKeZE5m8Rx",
        "actors": [
            {
                "key": "user",
                "title": "User",
                "identity": "e2d54eef-3748-4ceb-b723-23ff44a2512b"
            },
            {
                "key": "organization",
                "title": "Organization",
                "identity": "e8a1479e-d40f-4b54-a31d-15f39bdb00f5"
            }
        ],
        "current": {
            "key": ":success"
        }
    },
    {
        "_id": "8fd21874-c3f3-11e9-91e2-237252395578",
        "schema": "https://specs.livecontracts.io/v0.2.0/process/schema.json#",
        "title": "Basic system and user",
        "scenario": "2557288f-108e-4398-8d2d-7914ffd93150",
        "chain": "2c83KDmRCJwaKWky1jmtTRYmsgXAhmuDC8P12KpqqbrQKkY6UMECmKeZE5m8Rx",
        "actors": [
            {
                "key": "user",
                "title": "User",
                "identity": "1237288f-8u6f-3edt-8d2d-4f4ffd938vk"
            },
            {
                "key": "organization",
                "title": "Organization",
                "identity": "e8a1479e-d40f-4b54-a31d-15f39bdb00f5"
            }
        ],
        "current": {
            "key": ":success"
        }
    },
]);

db.getCollection("identities").insert([
    {
        /* organization */
        "_id": "1237288f-8u6f-3edt-8d2d-4f4ffd938vk",
        "node" : "amqps://localhost",
        "signkeys" : {
            "default": "57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn",
            "system": "FkU1XyfrCftc4pQKXCrrDyRLSnifX1SMvmx1CYiiyB3Y"
        },
        "encryptkey" : "9fSos8krst114LtaYGHQPjC3h1CQEHUQWEkYdbykrhHv",
        "authz": 0
    },
    {
        /* user */
        "_id": "e2d54eef-3748-4ceb-b723-23ff44a2512b",
        "signkeys": {
            "default": "AZeQurvj5mFHkPihiFa83nS2Fzxv3M75N7o9m5KQHUmo",
            "system": "C47Qse1VRCGnn978WB1kqvkcsd1oG8p9SfJXUbwVZ9vV"
        },
        "authz": 1
    },
    {
        /* organization */
        "_id": "6uk7288s-afe4-7398-8dbh-7914ffd930pl",
        "signkeys": {
            "default": "57FWtEbXoMKXj71FT84hcvCxN5z1CztbZ8UYJ2J49Gcn"
        },
        "authz": 10
    },
    {
        /* node */
        "_id": "e8a1479e-d40f-4b54-a31d-15f39bdb00f5",
        "signkeys": {
            "system": "3UDCFY6MojrPKaayHgAEqrnp99JhviSAiraJX8J1fJ9E"
        },
        "authz": 10
    },
    {
        /* participant */
        "_id": "14134336-e5e8-11e9-b414-778e97bfed1a",
        "signkeys": {
            "default": "AWDABMBzKd2oGoL8sxGxGGvL28dNzSibVkira6CHpuTX"
        },
        "authz": 0
    }
]);
