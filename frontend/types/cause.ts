interface CauseType {
    title: string,
    description: string,
    category: string,
    target_amount: number
    collected_amount: number,
    media_url: string,
    donations?: object[],
    created_at: string
}
