mutation {
    accountUpdatePassword(
        password: "password"
    ) {
        id
        createdAt
        updatedAt
        name
        registration
        status
        passwordUpdate
        email
        phone
        emailVerification
        phoneVerification
        prefs
    }
}
