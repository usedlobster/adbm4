# API Documentation

## Authentication

If authentication is required, an access token should be provided in the header of each request.

`Authorization: Bearer <token>`

## Responses

If an access token has expired, (or unreadable ) the api will return 
```json
{ "expired" : "true" }
```

### Authorize  with Username and Password

**Endpoint:** `POST /v1/login/uap`

**Description:** Authenticates a user with username and password credentials.

#### No access token needed 

#### Request Parameters

| Parameter | Type   | Required | Description                       |
|-----------|--------|----------|-----------------------------------|
| user      | string | Yes      | Username (converted to lowercase) |
| pass      | string | Yes      | User password                     |
| vcode     | string | Yes      | SHA256 hash of verification code  |

#### No authentication needed



The given user, is looked up in the database , if valid and the password matches , an authid is returned along with a suggested project pid . The suggest pid is the last used pid for that user/device. 

#### Request Example

```json
{   
    "user" : "username",
    "pass" : "password",
    "vcode" : "00000...." 
}

```

#### Response Example

```json
{   
    "authid" : "1234567890" ,
    "pid" : 1 
}
```

### Exchange Authid for Access Tokens

**Endpoint:** `POST /v1/login/exg`

**Description:** Exchanges an authid for access tokens.

#### Request Parameters

| Parameter | Type   | Required | Description                         |
|-----------|--------|----------|-------------------------------------|
| authid    | string | Yes      | Authid returned from login endpoint |
| vcode     | string | Yes      | Unhashed verification code          |
| pid       | int    | Yes      | Project id to use for login         | 

#### Response Example







