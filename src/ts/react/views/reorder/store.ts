import apiFetch from "@wordpress/api-fetch";
import { createReduxStore, dispatch, register, select } from "@wordpress/data";
import type { NodeModel } from "@minoru/react-dnd-treeview";
import type {
	ChildrenMeta,
	PostData,
	PostsResponse,
	ReorderConfig,
} from "./types";

export const STORE_NAME = "reorder-posts/data";

const ROOT_PARENT_ID = 0;
const CHILDREN_PER_PAGE = 500;

type ReorderState = {
	isInitialLoading: boolean;
	config: ReorderConfig | null;
	nodesById: Record<number, PostData>;
	treeOrder: Record<number, number[]>;
	openIds: number[];
	rootPagination: {
		offset: number;
		hasMore: boolean;
		isLoading: boolean;
		isLoadingMore: boolean;
		total: number;
	};
	childrenMeta: Record<number, ChildrenMeta>;
	isDragging: boolean;
	error: string | null;
};

const DEFAULT_CHILDREN_META: ChildrenMeta = {
	isLoading: false,
	isLoaded: false,
	hasMore: false,
	offset: 0,
};

const DEFAULT_STATE: ReorderState = {
	isInitialLoading: true,
	config: null,
	nodesById: {},
	treeOrder: { [ROOT_PARENT_ID]: [] },
	openIds: [],
	rootPagination: {
		offset: 0,
		hasMore: false,
		isLoading: false,
		isLoadingMore: false,
		total: 0,
	},
	childrenMeta: {},
	isDragging: false,
	error: null,
};

const isNodeDroppable = (node: PostData, state: ReorderState): boolean => {
	if (state.config?.hierarchical) {
		return true;
	}
	if (node.has_children) {
		return true;
	}
	const childOrder = state.treeOrder[node.id];
	return Boolean(childOrder && childOrder.length > 0);
};

export const treeOrderToModels = (
	state: ReorderState
): NodeModel<PostData>[] => {
	const models: NodeModel<PostData>[] = [];
	const visited = new Set<number>();

	const walk = (parentId: number) => {
		const ids = state.treeOrder[parentId] ?? [];
		ids.forEach((id) => {
			if (visited.has(id)) {
				return;
			}
			visited.add(id);
			const node = state.nodesById[id];
			if (!node) {
				return;
			}
			models.push({
				id: node.id,
				parent: node.parent ?? ROOT_PARENT_ID,
				text: node.title,
				data: node,
				droppable: isNodeDroppable(node, state),
			});
			walk(id);
		});
	};

	walk(ROOT_PARENT_ID);
	return models;
};

const treeToTreeOrder = (
	tree: NodeModel<PostData>[]
): Record<number, number[]> => {
	const order: Record<number, number[]> = {};
	tree.forEach((node) => {
		const parentId = Number(node.parent ?? ROOT_PARENT_ID);
		if (!order[parentId]) {
			order[parentId] = [];
		}
		order[parentId].push(Number(node.id));
	});
	return order;
};

const buildPostsPath = (
	config: ReorderConfig,
	offset: number,
	options: {
		parentId?: number;
		postsPerPage?: number;
	} = {}
): string => {
	const params = new URLSearchParams({
		post_type: config.postType,
		posts_per_page: String(options.postsPerPage ?? config.postsPerPage),
		offset: String(offset),
		order: "ASC",
		nonce: config.nonce,
		hierarchical: config.hierarchical ? "true" : "false",
		post_status: config.postStatus.join(","),
	});

	if (config.hierarchical && options.parentId !== undefined) {
		params.set("parent_id", String(options.parentId));
	}

	return `/reorder-posts/v1/posts?${params.toString()}`;
};

const mergePostsIntoState = (
	state: ReorderState,
	posts: PostData[],
	listParentId: number,
	bucketByPostParent = false
): ReorderState => {
	const nodesById = { ...state.nodesById };
	const treeOrder = { ...state.treeOrder };

	posts.forEach((post) => {
		const bucketParentId = bucketByPostParent
			? Number(post.parent ?? ROOT_PARENT_ID)
			: listParentId;

		nodesById[post.id] = {
			...post,
			parent: post.parent ?? listParentId,
		};

		const siblingIds = treeOrder[bucketParentId] ?? [];
		if (!siblingIds.includes(post.id)) {
			treeOrder[bucketParentId] = [...siblingIds, post.id];
		}
	});

	return {
		...state,
		nodesById,
		treeOrder,
	};
};

const actions = {
	setInitialLoading(isInitialLoading: boolean) {
		return {
			type: "SET_INITIAL_LOADING",
			isInitialLoading,
		} as const;
	},
	setConfig(config: ReorderConfig) {
		return {
			type: "SET_CONFIG",
			config,
		} as const;
	},
	setFetchingRoots(isLoading: boolean, isLoadingMore: boolean) {
		return {
			type: "SET_FETCHING_ROOTS",
			isLoading,
			isLoadingMore,
		} as const;
	},
	receiveRootPosts(response: PostsResponse) {
		return {
			type: "RECEIVE_ROOT_POSTS",
			response,
		} as const;
	},
	setFetchingChildren(parentId: number, isLoading: boolean) {
		return {
			type: "SET_FETCHING_CHILDREN",
			parentId,
			isLoading,
		} as const;
	},
	receiveChildPosts(parentId: number, response: PostsResponse) {
		return {
			type: "RECEIVE_CHILD_POSTS",
			parentId,
			response,
		} as const;
	},
	setOpenIds(openIds: number[]) {
		return {
			type: "SET_OPEN_IDS",
			openIds,
		} as const;
	},
	applyTreeDrop(tree: NodeModel<PostData>[]) {
		return {
			type: "APPLY_TREE_DROP",
			tree,
		} as const;
	},
	setDragging(isDragging: boolean) {
		return {
			type: "SET_DRAGGING",
			isDragging,
		} as const;
	},
	setError(error: string | null) {
		return {
			type: "SET_ERROR",
			error,
		} as const;
	},
};

const reducer = (
	state: ReorderState = DEFAULT_STATE,
	action: ReturnType<(typeof actions)[keyof typeof actions]>
): ReorderState => {
	switch (action.type) {
		case "SET_INITIAL_LOADING":
			return {
				...state,
				isInitialLoading: action.isInitialLoading,
			};
		case "SET_CONFIG":
			return {
				...state,
				config: action.config,
			};
		case "SET_FETCHING_ROOTS":
			return {
				...state,
				rootPagination: {
					...state.rootPagination,
					isLoading: action.isLoading,
					isLoadingMore: action.isLoadingMore,
				},
				error: null,
			};
		case "RECEIVE_ROOT_POSTS": {
			const listParentId = ROOT_PARENT_ID;
			const merged = mergePostsIntoState(
				state,
				action.response.posts,
				listParentId,
				!state.config?.hierarchical
			);
			return {
				...merged,
				rootPagination: {
					offset: action.response.offset,
					hasMore: action.response.more_posts,
					isLoading: false,
					isLoadingMore: false,
					total: action.response.total,
				},
			};
		}
		case "SET_FETCHING_CHILDREN": {
			const current =
				state.childrenMeta[action.parentId] ?? DEFAULT_CHILDREN_META;
			return {
				...state,
				childrenMeta: {
					...state.childrenMeta,
					[action.parentId]: {
						...current,
						isLoading: action.isLoading,
					},
				},
			};
		}
		case "RECEIVE_CHILD_POSTS": {
			const merged = mergePostsIntoState(
				state,
				action.response.posts,
				action.parentId
			);
			const current =
				state.childrenMeta[action.parentId] ?? DEFAULT_CHILDREN_META;
			return {
				...merged,
				childrenMeta: {
					...merged.childrenMeta,
					[action.parentId]: {
						...current,
						isLoading: false,
						isLoaded: !action.response.more_posts,
						hasMore: action.response.more_posts,
						offset: action.response.offset,
					},
				},
			};
		}
		case "SET_OPEN_IDS":
			return {
				...state,
				openIds: action.openIds,
			};
		case "APPLY_TREE_DROP": {
			const nodesById = { ...state.nodesById };
			action.tree.forEach((node) => {
				const id = Number(node.id);
				if (nodesById[id]) {
					nodesById[id] = {
						...nodesById[id],
						parent: Number(node.parent ?? ROOT_PARENT_ID),
					};
				}
			});
			return {
				...state,
				nodesById,
				treeOrder: treeToTreeOrder(action.tree),
			};
		}
		case "SET_DRAGGING":
			return {
				...state,
				isDragging: action.isDragging,
			};
		case "SET_ERROR":
			return {
				...state,
				error: action.error,
				rootPagination: {
					...state.rootPagination,
					isLoading: false,
					isLoadingMore: false,
				},
			};
		default:
			return state;
	}
};

const selectors = {
	getIsInitialLoading(state: ReorderState) {
		return state.isInitialLoading;
	},
	getConfig(state: ReorderState) {
		return state.config;
	},
	getTreeModels(state: ReorderState) {
		return treeOrderToModels(state);
	},
	getOpenIds(state: ReorderState) {
		return state.openIds;
	},
	getRootOffset(state: ReorderState) {
		return state.rootPagination.offset;
	},
	getHasMoreRoots(state: ReorderState) {
		return state.rootPagination.hasMore;
	},
	getIsLoadingMore(state: ReorderState) {
		return state.rootPagination.isLoadingMore;
	},
	getIsDragging(state: ReorderState) {
		return state.isDragging;
	},
	getError(state: ReorderState) {
		return state.error;
	},
	getNodeById(state: ReorderState, nodeId: number) {
		return state.nodesById[nodeId];
	},
	getNodeModelById(state: ReorderState, nodeId: number) {
		const node = state.nodesById[nodeId];
		if (!node) {
			return undefined;
		}

		return {
			id: node.id,
			parent: node.parent ?? ROOT_PARENT_ID,
			text: node.title,
			data: node,
			droppable: isNodeDroppable(node, state),
		};
	},
	getChildrenMeta(state: ReorderState, parentId: number) {
		return state.childrenMeta[parentId] ?? DEFAULT_CHILDREN_META;
	},
	isNodeChildrenLoading(state: ReorderState, parentId: number) {
		return state.childrenMeta[parentId]?.isLoading ?? false;
	},
};

const store = createReduxStore(STORE_NAME, {
	reducer,
	actions,
	selectors,
});

register(store);

type ActionCreators = typeof actions;

export type ReorderStoreDispatch = {
	[K in keyof ActionCreators]: (
		...args: Parameters<ActionCreators[K]>
	) => void;
};

export type BoundSelectors = {
	getIsInitialLoading: () => boolean;
	getConfig: () => ReorderConfig | null;
	getTreeModels: () => NodeModel<PostData>[];
	getOpenIds: () => number[];
	getRootOffset: () => number;
	getHasMoreRoots: () => boolean;
	getIsLoadingMore: () => boolean;
	getIsDragging: () => boolean;
	getError: () => string | null;
	getNodeById: (nodeId: number) => PostData | undefined;
	getNodeModelById: (nodeId: number) => NodeModel<PostData> | undefined;
	getChildrenMeta: (parentId: number) => ChildrenMeta;
	isNodeChildrenLoading: (parentId: number) => boolean;
};

export const getBoundSelectors = (
	selectStore: (name: string) => unknown
): BoundSelectors => selectStore(STORE_NAME) as unknown as BoundSelectors;

const storeSelect = (): BoundSelectors =>
	select(store) as unknown as BoundSelectors;

const storeDispatch = (): ReorderStoreDispatch =>
	dispatch(store) as unknown as ReorderStoreDispatch;

export async function fetchRootPosts(
	offset: number,
	isLoadingMore: boolean
): Promise<void> {
	const storeActions = storeDispatch();
	const config = storeSelect().getConfig();

	if (!config) {
		return;
	}

	storeActions.setFetchingRoots(!isLoadingMore, isLoadingMore);
	storeActions.setError(null);

	try {
		const response = await apiFetch<PostsResponse>({
			path: buildPostsPath(config, offset, {
				parentId: config.hierarchical ? ROOT_PARENT_ID : undefined,
			}),
			method: "GET",
		});

		storeActions.receiveRootPosts(response);
	} catch (error) {
		console.error(error);
		storeActions.setError(
			error instanceof Error ? error.message : "Failed to load posts."
		);
	} finally {
		storeActions.setInitialLoading(false);
	}
}

export async function initializeReorderStore(
	config: ReorderConfig
): Promise<void> {
	const storeActions = storeDispatch();
	storeActions.setConfig(config);
	await fetchRootPosts(0, false);
}

export async function loadMoreRoots(): Promise<void> {
	const storeSelector = storeSelect();

	if (
		!storeSelector.getHasMoreRoots() ||
		storeSelector.getIsLoadingMore() ||
		storeSelector.getIsInitialLoading()
	) {
		return;
	}

	await fetchRootPosts(storeSelector.getRootOffset(), true);
}

export async function ensureChildrenLoaded(parentId: number): Promise<void> {
	const storeSelector = storeSelect();
	const storeActions = storeDispatch();
	const config = storeSelector.getConfig();
	const node = storeSelector.getNodeById(parentId);

	if (!config?.hierarchical || !node?.has_children) {
		return;
	}

	const meta = storeSelector.getChildrenMeta(parentId);

	if (meta.isLoading || meta.isLoaded) {
		return;
	}

	storeActions.setFetchingChildren(parentId, true);
	storeActions.setError(null);

	try {
		let currentMeta = meta;

		while (!currentMeta.isLoaded) {
			const response = await apiFetch<PostsResponse>({
				path: buildPostsPath(config, currentMeta.offset, {
					parentId,
					postsPerPage: CHILDREN_PER_PAGE,
				}),
				method: "GET",
			});

			storeActions.receiveChildPosts(parentId, response);
			currentMeta = storeSelect().getChildrenMeta(parentId);
		}
	} catch (error) {
		console.error(error);
		storeActions.setFetchingChildren(parentId, false);
		storeActions.setError(
			error instanceof Error
				? error.message
				: "Failed to load child posts."
		);
	}
}

export { actions, selectors, store };
