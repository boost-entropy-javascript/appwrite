mutation {
    databasesCreate(
        databaseId: "[DATABASE_ID]",
        name: "[NAME]"
    ) {
        id
        name
        createdAt
        updatedAt
    }
}
