db = db.getSiblingDB('lto_workflow_tests');

/*
 * Create MongoDB elements needed for the API tests
 */

// TODO update the data to the new schema.

db.getCollection("scenarios").insert([
    {
        "schema": "test",
        "_id": "dummy-wizard",
        "name": "Dummy Wizard",
        "title": "Go through 3 steps",
        "start": "step1",
        "published": true,
        "actors": [
            {
                "key": "dummy",
                "title": "Dummy",
                "iam_user": true
            }
        ],
        "actions": [
            {
                "key": "step1",
                "display": true,
                "definition": "custom",
                "title": "The first step",
                "actor": "dummy",
                "responses": [
                    {
                        "key": "ok",
                        "transition": "step2",
                        "title": "Next"
                    },
                    {
                        "key": "cancel",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "step2",
                "display": true,
                "definition": "custom",
                "title": "The second step",
                "actor": "dummy",
                "responses": [
                    {
                        "key": "ok",
                        "transition": "step3",
                        "title": "Next"
                    },
                    {
                        "key": "cancel",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "step3",
                "display": true,
                "definition": "custom",
                "title": "The last step",
                "actor": "dummy",
                "responses": [
                    {
                        "key": "ok",
                        "transition": ":success",
                        "title": "Done"
                    },
                    {
                        "key": "cancel",
                        "transition": ":failed"
                    }
                ],
                "authz": {
                    "foo": "bar"
                }
            }
        ]
    },
    {
        "schema": "test",
        "_id": "legal-identification",
        "name": "Legal Identification",
        "title": "Identification",
        "start": "step1",
        "actors": [
            {
                "key": "client",
                "title": "Client",
                "iam_user": true
            },
            {
                "key": "lawyer",
                "title": "Lawyer"
            }
        ],
        "actions": [
            {
                "key": "step1",
                "display": true,
                "title": "Fill out a form",
                "definition": "form",
                "actor": "client",
                "responses": [
                    {
                        "key": "ok",
                        "title": "Filled out form",
                        "transition": "step_validate"
                    },
                    {
                        "key": "cancel",
                        "title": "Cancelled",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "step_validate",
                "display": true,
                "definition": "condition",
                "title": "Validate the data",
                "hidden": true,
                "trigger": {
                    "type": "validation",
                    "rules": {"_ref": "scenario.actions.step1.validation"}, // Should be '@'
                    "value": {"_ref": "scenario.actions.step1.value"},
                },
                "validation": [
                    { "rule": "notEmpty", "property": "first_name" },
                    { "rule": "notEmpty", "property": "last_name" },
                ],
                "value": {"_ref": "data"},
                "responses": [
                    {
                        "key": "true",
                        "transition": "step2"
                    },
                    {
                        "key": "false",
                        "transition": ":failed"
                    },
                    {
                        "key": "error",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "step2",
                "display": true,
                "title": "Review the data",
                "definition": "custom",
                "actor": "lawyer",
                "message": {
                    "_tpl": "<dl><dt>First name</dt><dd>{{ data.first_name }}</dd><dt>Last name</dt><dd>{{ data.last_name }}</dd><dt>Date of birth</dt><dd>{{ data.birthday }}</dd></dl>"
                },
                "responses": [
                    {
                        "key": "ok",
                        "title": "Reviewed the data",
                        "label": "Approve",
                        "transition": "step3",
                        "update": {
                            "select": "data.message"
                        }
                    },
                    {
                        "key": "cancel",
                        "title": "Cancelled",
                        "label": "Deny",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "step3",
                "display": true,
                "title": "Upload your ID",
                "definition": "upload-asset",
                "actor": "client",
                "responses": [
                    {
                        "key": "ok",
                        "label": "Uploaded ID",
                        "transition": "step4"
                    },
                    {
                        "key": "cancel",
                        "label": "Cancelled",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "step4",
                "display": true,
                "title": "Approve the admission",
                "definition": "custom",
                "actor": "lawyer",
                "responses": [
                    {
                        "key": "ok",
                        "label": "Approved the admission",
                        "title": "Approve admission",
                        "transition": ":success"
                    },
                    {
                        "key": "decline",
                        "label": "Declined the admission",
                        "title": "Decline admission",
                        "transition": ":success"
                    }
                ]
            }
        ]
    },
    {
        "schema": "test",
        "_id": "global-data",
        "name": "Global Data",
        "title": "Use global data",
        "start": "step1",
        "contract": {
            "template": "dummy-template",
            "paragraph": "Dummy Paragraph"
        },
        "actors": [
            {
                "key": "user",
                "title": "User",
                "iam_user": true
            },
            {
                "key": "bar",
                "title": "should remain the same",
                "iam_user": true
            }
        ],
        "actions" : [
            {
                "key" : "step1",
                "display" : true,
                "trigger" : {
                    "type" : "nop",
                    "response" : "ok",
                    "data" : {
                        "foo" : "bar",
                        "userId": {
                            "<ref>": "global.session.user.id" 
                        },
                        "userName": {
                            "<ref>": "global.session.user.name"
                        },
                        "config": {
                            "<ref>": "global.config.some_values"
                        },
                        "actors":[
                            {
                                "key" : "user",
                                "title" : "User",
                                "id": {
                                    "<ref>": "global.session.user.id" 
                                },
                                "organization": "ccc000000000000000000111"
                            }
                        ]
                    }
                },
                "responses" : [ 
                    {
                        "key" : "ok",
                        "update" : {
                            "select" : "$",
                            "patch" : true
                        },
                        "transition" : ":success"
                    }
                ]
            }
        ]
    },
    {
        "schema": "test",
        "_id": "schema-test",
        "name": "Schema Test",
        "title": "Go through 3 actions",
        "start": "action1",
        "actors": [
            {
                "key": "actor1",
                "title": "Actor 1",
                "id": null
            },
            {
                "key": "actor2",
                "title": "Actor 2",
                "id": "actor2"
            }
        ],
        "actions": [
            {
                "key": "action1",
                "display": true,
                "definition": "custom",
                "title": "The first action",
                "actor": "actor1",
                "responses": [
                    {
                        "key": "ok",
                        "transition": "state1",
                        "title": "Next"
                    },
                    {
                        "key": "cancel",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "action2",
                "display": true,
                "definition": "custom",
                "title": "The second action",
                "actor": "actor2",
                "responses": [
                    {
                        "key": "ok",
                        "transition": "state2",
                        "title": "Next"
                    },
                    {
                        "key": "back",
                        "transition": "state1"
                    }
                ]
            },
            {
                "key": "action3",
                "display": true,
                "definition": "custom",
                "title": "The third action",
                "actor": "actor1",
                "responses": [
                    {
                        "key": "ok",
                        "transition": ":success",
                        "title": "Next"
                    },
                    {
                        "key": "back",
                        "transition": "state2"
                    }
                ]
            }
        ],
        "states": [
            {
                "key": ":initial",
                "title": "The initial state",
                "actions": ["action1", "action2"]
            },
            {
                "key": "state1",
                "title": "The first state",
                "actions": ["action2"]
            },
            {
                "key": "state2",
                "title": "The second state",
                "actions": ["action3"]
            }
        ]
    },
    {
        "schema": "test",
        "_id": "00000000-0000-0000-0000-000000000001",
        "version": 2,
        "name": "Schema Test (done)",
        "title": "Go through 3 actions",
        "start": "action1",
        "actors": [
            {
                "key": "actor1",
                "title": "Actor 1",
                "id": "actor1"
            },
            {
                "key": "actor2",
                "title": "Actor 2",
                "id": "actor2"
            }
        ],
        "actions": [
            {
                "key": "action1",
                "display": true,
                "definition": "custom",
                "title": "The first action",
                "actor": "actor1",
                "responses": [
                    {
                        "key": "ok",
                        "transition": "state1",
                        "title": "Next"
                    },
                    {
                        "key": "cancel",
                        "transition": ":failed"
                    }
                ]
            },
            {
                "key": "action2",
                "display": true,
                "definition": "custom",
                "title": "The second action",
                "actor": "actor2",
                "responses": [
                    {
                        "key": "ok",
                        "transition": "state2",
                        "title": "Next"
                    },
                    {
                        "key": "back",
                        "transition": "state1"
                    }
                ]
            },
            {
                "key": "action3",
                "display": true,
                "definition": "custom",
                "title": "The third action",
                "actor": "actor1",
                "responses": [
                    {
                        "key": "ok",
                        "transition": ":success",
                        "title": "Next"
                    },
                    {
                        "key": "back",
                        "transition": "state2"
                    }
                ]
            }
        ],
        "states": [
            {
                "key": ":initial",
                "title": "The initial state",
                "actions": ["action1", "action2"]
            },
            {
                "key": "state1",
                "title": "The first state",
                "actions": ["action2"]
            },
            {
                "key": "state2",
                "title": "The second state",
                "actions": ["action3"]
            }
        ]
    }
]);


db.getCollection("processes").insert([
    {
        "_id": ObjectId("000000000000000000000012"),
        "sort": true,
        "title": "Identification",
        "creation": new Date("2016-01-01T00:00:00+00:00"),
        "assets": {
            "photo": {
                "url": "http://localhost:3000/service/dms/400/300/cats/"
            },
            "cats": [
                {
                    "url": "http://localhost:3000/service/dms/400/300/cats/"
                },
                {
                    "url": "http://localhost:3000/service/dms/500/500/cats/"
                }
            ] 
        },
        "data": {
            "first_name": "David",
            "last_name": "White",
            "birthday": "01-02-1970T00:00:00+00:00",
            "address": {
                "street": "Lindelaan 1",
                "postalcode": "3500 AA",
                "city": "Utrecht"
            }
        },
        "private_data": {
            "secret": "123abc"
        },
        "scenario": {
            "_id": "legal-identification",
            "name": "Legal Identification",
            "title": "Identification",
            "start": "step1",
            "actors": [
                {
                    "key": "client",
                    "title": "Client",
                    "iam_user": true
                },
                {
                    "key": "lawyer",
                    "title": "Lawyer",
                    "iam_user": true
                }
            ],
            "actions": [
                {
                    "key": "step1",
                    "display": true,
                    "title": "Fill out a form",
                    "definition": "form",
                    "actor": "client",
                    "due_duration": "1 day",
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Filled out form",
                            "transition": "step_validate"
                        },
                        {
                            "key": "cancel",
                            "title": "Cancelled",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step_validate",
                    "display": true,
                    "definition": "condition",
                    "title": "Validate the data",
                    "hidden": true,
                    "trigger": {
                        "type": "validation",
                        "rules": {"_ref": "scenario.actions.step1.validation"}, // Should be '@'
                        "value": {"_ref": "scenario.actions.step1.value"},
                    },
                    "validation": [
                        { "rule": "notEmpty", "property": "first_name" },
                        { "rule": "notEmpty", "property": "last_name" },
                    ],
                    "value": {"_ref": "data"},
                    "responses": [
                        {
                            "key": "true",
                            "transition": "step2"
                        },
                        {
                            "key": "false",
                            "transition": ":failed"
                        },
                        {
                            "key": "error",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step2",
                    "display": true,
                    "title": "Review the data",
                    "definition": "custom",
                    "actor": "lawyer",
                    "due_duration": "2 days",
                    "message": {
                        // differs from original scenario on purpose
                        "_tpl": "<dl><dt>First name</dt><dd>{{ data.first_name }}</dd><dt>Last name</dt><dd>{{ data.last_name }}</dd><dt>Birthday</dt><dd>{{ data.birthday }}</dd></dl>"
                    },
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Reviewed the data",
                            "label": "Approve",
                            "transition": "step3",
                            "update": {
                                "select": "data.message"
                            }
                        },
                        {
                            "key": "cancel",
                            "title": "Cancelled",
                            "label": "Deny",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step3",
                    "display": true,
                    "title": "Upload your ID",
                    "definition": "upload-asset",
                    "actor": "client",
                    "due_duration": "2 days",
                    "responses": [
                        {
                            "key": "ok",
                            "label": "Uploaded ID",
                            "transition": "step4"
                        },
                        {
                            "key": "cancel",
                            "label": "Cancelled",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step4",
                    "display": true,
                    "title": "Approve the admission",
                    "definition": "custom",
                    "actor": "lawyer",
                    "due_duration": "2 days",
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Approved the admission",
                            "label": "Approve admission",
                            "transition": ":success"
                        },
                        {
                            "key": "decline",
                            "title": "Declined the admission",
                            "label": "Decline admission",
                            "transition": ":success"
                        }
                    ]
                }
            ]
        },
        "actors": [
            {
                "key": "client",
                "email": "david@example.com",
                "title": "Client",
                "name": "David White",
                "iam_user": true
            },
            {
                "key": "lawyer",
                "id": "a00000000000000000000011",
                "email": "peter@example.com",
                "title": "Lawyer",
                "name": "Peter Blue",
                "iam_user": true
            }
        ],
        "previous": [
            {
                "key": "step1",
                "title": "Filled out form",
                "definition": "form",
                "actor": {
                    "key": "client",
                    "email": "david@example.com",
                    "title": "Client",
                    "name": "David White"
                },
                "response": {
                    "key": "ok",
                    "transition": "step2"
                },
                "activation_date": "2016-01-01T00:00:00+00:00"
            }
        ],
        "current": {
            "key": "step2",
            "activation_date": new Date("2016-01-03T00:00:00+00:00"),
            "due_date": new Date("2016-01-04T00:00:00+00:00"),
            "actor": {
                "key": "lawyer",
                "id": "a00000000000000000000011",
                "email": "peter@example.com",
                "iam_user": true
            }
        },
        "comments": [
            {
                "message": "I am new here",
                "user": {
                    "id": "000000000000000000000001"
                },
                "date": "2016-10-05T15:59:58+02:00"
            },
            {
                "message": "Welcome",
                "user": {
                    "id": "000000000000000000000111"
                },
                "date": "2016-10-05T16:01:12+02:00"
            }
        ]
    },
    {
        "_id": ObjectId("000000000000000000000013"),
        "sort": true,
        "title": "Identification second",
        "creation": "2016-01-04",
        "scenario": {
            "_id": "legal-identification",
            "name": "Legal Identification",
            "title": "Identification",
            "start": "step1",
            "actors": [
                {
                    "key": "client",
                    "title": "Client",
                    "iam_user": true
                },
                {
                    "key": "lawyer",
                    "title": "Lawyer",
                    "iam_user": true
                }
            ],
            "actions": [
                {
                    "key": "step1",
                    "display": true,
                    "title": "Fill out a form",
                    "definition": "form",
                    "actor": "client",
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Filled out form",
                            "transition": "step_validate"
                        },
                        {
                            "key": "cancel",
                            "title": "Cancelled",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step_validate",
                    "display": true,
                    "definition": "condition",
                    "title": "Validate the data",
                    "hidden": true,
                    "trigger": {
                        "type": "validation",
                        "rules": {"_ref": "scenario.actions.step1.validation"}, // Should be '@'
                        "value": {"_ref": "scenario.actions.step1.value"},
                    },
                    "validation": [
                        { "rule": "notEmpty", "property": "first_name" },
                        { "rule": "notEmpty", "property": "last_name" },
                    ],
                    "value": {"_ref": "data"},
                    "responses": [
                        {
                            "key": "true",
                            "transition": "step2"
                        },
                        {
                            "key": "false",
                            "transition": ":failed"
                        },
                        {
                            "key": "error",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step2",
                    "display": true,
                    "title": "Review the data",
                    "definition": "custom",
                    "actor": "lawyer",
                    "message": {
                        // differs from original scenario on purpose
                        "_tpl": "<dl><dt>First name</dt><dd>{{ data.first_name }}</dd><dt>Last name</dt><dd>{{ data.last_name }}</dd><dt>Birthday</dt><dd>{{ data.birthday }}</dd></dl>"
                    },
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Reviewed the data",
                            "label": "Approve",
                            "transition": "step3",
                            "update": {
                                "select": "data.message"
                            }
                        },
                        {
                            "key": "cancel",
                            "title": "Cancelled",
                            "label": "Deny",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step3",
                    "display": true,
                    "title": "Upload your ID",
                    "definition": "upload-asset",
                    "actor": "client",
                    "responses": [
                        {
                            "key": "ok",
                            "label": "Uploaded ID",
                            "transition": "step4"
                        },
                        {
                            "key": "cancel",
                            "label": "Cancelled",
                            "transition": ":failed"
                        }
                    ]
                },
                {
                    "key": "step4",
                    "display": true,
                    "title": "Approve the admission",
                    "definition": "custom",
                    "actor": "lawyer",
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Approved the admission",
                            "label": "Approve admission",
                            "transition": ":success"
                        },
                        {
                            "key": "decline",
                            "title": "Declined the admission",
                            "label": "Decline admission",
                            "transition": ":success"
                        }
                    ]
                }
            ]
        },
        "actors": [
            {
                "key": "client",
                "email": "david@example.com",
                "title": "Client",
                "name": "David White",
                "iam_user": true
            },
            {
                "key": "lawyer",
                "id": "a00000000000000000000011",
                "email": "peter@example.com",
                "title": "Lawyer",
                "name": "Peter Blue",
                "iam_user": true
            }
        ],
        "previous": [],
        "current": {
                "key": "step1",
                "title": "Filled out form",
                "definition": "form",
                "actor": {
                    "key": "client",
                    "email": "david@example.com",
                    "title": "Client",
                    "name": "David White",
                    "iam_user": true
                },
                "response": {
                    "key": "ok",
                    "transition": "step2"
                },
                "activation_date": new Date("2016-01-04")
        },
        "comments": []
    },
    {
        "_id": ObjectId("000000000000000000000020"),
        "title": "Current action with requirements",
        "creation": "2017-04-24",
        "assets": {},
        "data": {},
        "scenario": {
            "_id": "current-action-requirements",
            "name": "Current action with requirements",
            "title": "Current action with requirements",
            "start": "step1",
            "actors": [
                {
                    "key": "notary",
                    "title": "Notary",
                    "requirement": [
                        "affiliate"
                    ],
                    "iam_user": true
                }
            ],
            "actions": [
                {
                    "key": "step1",
                    "display": true,
                    "title": "Give approval",
                    "definition": "foo",
                    "actor": "notary",
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Approve",
                            "transition": ":success"
                        },
                        {
                            "key": "cancel",
                            "title": "Cancelled",
                            "transition": ":failed"
                        }
                    ]
                }
            ]
        },
        "actors": [
            {
                "key": "notary",
                "title": "Notary",
                "requirement": [
                    "affiliate"
                ],
                "iam_user": true
            }
        ],
        "previous": [],
        "current": {
            "key": "step1",
            "activation_date": new Date("2017-04-24"),
            "actor": {
                "key": "notary",
                "title": "Notary",
                "requirement": [
                    "affiliate"
                ]
            }
        },
        "comments": []
    },
    {
        "_id": ObjectId("000000000000000000000030"),
        "title": "Current action with only organization as actor",
        "creation": "2017-04-22",
        "assets": {},
        "data": {},
        "scenario": {
            "_id": "current-action-only-organization",
            "name": "Current action with only organization as actor",
            "title": "Current action with only organization as actor",
            "start": "step1",
            "actors": [
                {
                    "key": "notary",
                    "title": "Notary",
                    "id": null,
                    "organization": "ccc000000000000000000001",
                    "iam_user": true
                }
            ],
            "actions": [
                {
                    "key": "step1",
                    "display": true,
                    "title": "Give approval",
                    "definition": "foo",
                    "actor": "notary",
                    "responses": [
                        {
                            "key": "ok",
                            "title": "Approve",
                            "transition": ":success"
                        },
                        {
                            "key": "cancel",
                            "title": "Cancelled",
                            "transition": ":failed"
                        }
                    ]
                }
            ]
        },
        "actors": [
            {
                "key": "notary",
                "title": "Notary",
                "id": null,
                "organization": "ccc000000000000000000001",
                "iam_user": true
            }
        ],
        "previous": [],
        "current": {
            "key": "step1",
            "activation_date": "2017-04-22",
            "actor": {
                "key": "notary",
                "title": "Notary",
                "id": null,
                "organization": "ccc000000000000000000001"
            }
        },
        "comments": []
    },
    {
        "_id": ObjectId("000000000000000000000040"),
        "sort": true,
        "title": "Subaction",
        "creation": "2016-01-04",
        "scenario": {
            "_id": "subaction",
            "name": "Subaction",
            "title": "Subaction",
            "start": "step1",
            "actors": [
                {
                    "key": "client",
                    "title": "Client",
                    "iam_user": true
                }
            ],
            "actions": [
                {
                    "key": "step1",
                    "display": true,
                    "title": "Step 1",
                    "definition": "none",
                    "actor": "client",
                    "responses": [
                        {
                            "key": "ok",
                            "transition": "step2"
                        }
                    ],
                    "allow_actions": [
                        "sub_with_transition",
                        "sub_without_transition"
                    ]
                },
                {
                    "key": "step2",
                    "display": true,
                    "title": "Step 2",
                    "definition": "none",
                    "actor": "client",
                    "responses": [
                        {
                            "key": "ok",
                            "transition": "step3"
                        }
                    ]
                },
                {
                    "key": "step3",
                    "display": true,
                    "title": "Step 3",
                    "definition": "none",
                    "actor": "client",
                    "responses": [
                        {
                            "key": "ok",
                            "transition": ":success"
                        }
                    ]
                },
                {
                    "key": "sub_with_transition",
                    "display": true,
                    "title": "Sub with transition",
                    "definition": "none",
                    "actor": "client",
                    "responses": [
                        {
                            "key": "ok",
                            "transition": "step3"
                        }
                    ]
                },
                {
                    "key": "sub_without_transition",
                    "display": true,
                    "title": "Sub without transition",
                    "definition": "none",
                    "actor": "client",
                    "responses": [
                        {
                            "key": "foo",
                            "transition": false
                        }
                    ]
                }
            ]
        },
        "actors": [
            {
                "key": "client",
                "email": "david@example.com",
                "title": "Client",
                "name": "David White",
                "iam_user": true
            }
        ],
        "previous": [],
        "current": {
                "key": "step1",
                "title": "Step 1",
                "definition": "none",
                "actor": {
                    "key": "client",
                    "email": "david@example.com",
                    "title": "Client",
                    "name": "David White"
                },
                "response": {
                    "key": "ok",
                    "transition": "step2"
                },
                "activation_date": "2016-01-04",
                "allow_actions": [
                    "sub_with_transition",
                    "sub_without_transition"
                ]
        },
        "comments": []
    },
    {
        "_id" : ObjectId("00000000000000000000AAAA"),
        "title" : "Go through 3 actions",
        "scenario" : {
            "_id" : "schema-test",
            "schema" : "test",
            "name" : "Schema Test",
            "title" : "Go through 3 actions",
            "actors" : [ 
                {
                    "title" : "Actor 1",
                    "id" : "actor1",
                    "key" : "actor1"
                }, 
                {
                    "title" : "Actor 2",
                    "id" : "actor2",
                    "key" : "actor2"
                }
            ],
            "actions" : [ 
                {
                    "definition" : "custom",
                    "title" : "The first action",
                    "actor" : "actor1",
                    "responses" : [ 
                        {
                            "title" : "Next",
                            "transition" : "state1",
                            "key" : "ok"
                        }, 
                        {
                            "transition" : ":failed",
                            "key" : "cancel"
                        }
                    ],
                    "key" : "action1",
                    "display": true
                }, 
                {
                    "definition" : "custom",
                    "title" : "The second action",
                    "actor" : "actor2",
                    "responses" : [ 
                        {
                            "title" : "Next",
                            "transition" : "state2",
                            "key" : "ok"
                        }, 
                        {
                            "transition" : "state1",
                            "key" : "back"
                        }
                    ],
                    "key" : "action2",
                    "display": true
                }, 
                {
                    "definition" : "custom",
                    "title" : "The third action",
                    "actor" : "actor1",
                    "responses" : [ 
                        {
                            "title" : "Next",
                            "transition" : ":success",
                            "key" : "ok"
                        }, 
                        {
                            "transition" : "state2",
                            "key" : "back"
                        }
                    ],
                    "key" : "action3",
                    "display": true
                }
            ],
            "states" : [ 
                {
                    "title" : "The initial state",
                    "actions" : [ 
                        "action1", 
                        "action2"
                    ],
                    "transitions" : [],
                    "key" : ":initial"
                }, 
                {
                    "title" : "The first state",
                    "actions" : [ 
                        "action2"
                    ],
                    "transitions" : [],
                    "key" : "state1"
                }, 
                {
                    "title" : "The second state",
                    "actions" : [ 
                        "action3"
                    ],
                    "transitions" : [],
                    "key" : "state2"
                }, 
                {
                    "actions" : [],
                    "transitions" : [],
                    "key" : ":start"
                }, 
                {
                    "actions" : [],
                    "transitions" : [],
                    "key" : ":success"
                }, 
                {
                    "actions" : [],
                    "transitions" : [],
                    "key" : ":failed"
                }
            ],
            "assets" : {},
            "categories" : [],
            "start" : "action1"
        },
        "actors" : [ 
            {
                "title" : "Actor 1",
                "id" : "actor1",
                "key" : "actor1"
            }, 
            {
                "title" : "Actor 2",
                "id" : "actor2",
                "key" : "actor2"
            }
        ],
        "previous" : [ 
            {
                "definition" : "custom",
                "title" : "The first action",
                "actor" : {
                    "title" : "Actor 1",
                    "id" : "actor1",
                    "key" : "actor1"
                },
                "response" : {
                    "title" : "Next",
                    "transition" : "state1",
                },
                "response_date" : ISODate("2018-03-12T11:09:22.000Z"),
                "key" : "action1"
            }
        ],
        "current" : {
            "title" : "The first state",
            "actions" : [ 
                "action2"
            ],
            "transitions" : [],
            "key" : "state1",
            "display": true
        },
        "nextCount" : 1,
        "assets" : [],
        "previous_response" : "It's ok, but remember the titans",
        "creation" : ISODate("2018-03-12T11:09:22.000Z"),
        "comments" : []
    }
]);
