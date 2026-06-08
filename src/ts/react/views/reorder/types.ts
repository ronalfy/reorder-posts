export type PostData = {
	id: number;
	title: string;
	url: string;
	menu_order: number;
	parent?: number;
	has_children?: boolean;
	droppable?: boolean;
};

export type ReorderConfig = {
	postType: string;
	postsPerPage: number;
	hierarchical: boolean;
	postStatus: string[];
	nonce: string;
};

export type PostsResponse = {
	posts: PostData[];
	total: number;
	offset: number;
	more_posts: boolean;
};

export type ChildrenMeta = {
	isLoading: boolean;
	isLoaded: boolean;
	hasMore: boolean;
	offset: number;
};
