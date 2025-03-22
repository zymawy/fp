# API Relations Documentation

## Cause API Relations

The Causes API supports including related data in the responses. This helps reduce the number of requests needed and provides a more complete data structure for frontend developers.

### Default Includes

By default, the following relationships are always included in cause responses:

- `category` - The category the cause belongs to

### Available Includes

Additionally, the following relationships can be requested on demand:

- `partner` - Organization partnered with this cause (if any)
- `donations` - All donations made to this cause
- `updates` - All updates posted about the cause's progress

### How to Request Includes

To request additional relationships, use the `include` query parameter with comma-separated values:

```
GET /api/causes?include=partner,donations,updates
GET /api/causes/5dccc3b6-bc21-4b67-8b1f-521c381ccebb?include=partner,donations,updates
```

### Response Format

When relationships are included, you'll see them in two places:

1. In the `attributes` section, summary information about the relationship (e.g., counts)
2. In the `relationships` section, defining the relationship links
3. In the `included` section, the full details of the related resources

### Performance Considerations

Including relationships increases the response size and may impact performance. Only request the relationships you need for a particular view.

- For lists of causes, consider not including any additional relationships
- For single cause views, include the relevant relationships needed for that view

## Other API Endpoints

The same pattern applies to other endpoints in the API. Check the specific endpoint documentation for details on available includes. 