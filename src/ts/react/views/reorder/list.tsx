import React, { useCallback } from "react";
import {
	Tree,
	getBackendOptions,
	isAncestor,
	MultiBackend,
	type NodeModel,
} from "@minoru/react-dnd-treeview";
import { DndProvider } from "react-dnd";
import { Button } from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { reorderClasses } from "./classes";
import { PostData } from "./types";
import { CustomNode } from "./CustomNode";
import { Placeholder } from "./Placeholder";
import { CustomDragPreview } from "./CustomDragPreview";
import {
	getBoundSelectors,
	loadMoreRoots,
	store,
	type ReorderStoreDispatch,
} from "./store";

const List = ({ hierarchical = false }: { hierarchical?: boolean }) => {
	const reorderDispatch = useDispatch(store) as ReorderStoreDispatch;

	const { treeModels, openIds, isDragging, hasMoreRoots, isLoadingMore } =
		useSelect((selectStore) => {
			const store = getBoundSelectors(selectStore);
			return {
				treeModels: store.getTreeModels(),
				openIds: store.getOpenIds(),
				isDragging: store.getIsDragging(),
				hasMoreRoots: store.getHasMoreRoots(),
				isLoadingMore: store.getIsLoadingMore(),
			};
		}, []);

	const handleDrop = useCallback(
		(newTree: NodeModel<PostData>[]) => {
			reorderDispatch.applyTreeDrop(newTree);
		},
		[reorderDispatch]
	);

	const handleDragStart = useCallback(() => {
		reorderDispatch.setDragging(true);
	}, [reorderDispatch]);

	const handleDragEnd = useCallback(() => {
		reorderDispatch.setDragging(false);
	}, [reorderDispatch]);

	const handleChangeOpen = useCallback(
		(ids: NodeModel["id"][]) => {
			reorderDispatch.setOpenIds(ids.map(Number));
		},
		[reorderDispatch]
	);

	const handleLoadMore = useCallback(() => {
		void loadMoreRoots();
	}, []);

	const renderNode = useCallback(
		(
			node: NodeModel<PostData>,
			{
				depth,
				isOpen,
				onToggle,
				isDropTarget,
				isDragging: nodeIsDragging,
				hasChild,
			}: {
				depth: number;
				isOpen: boolean;
				onToggle: () => void;
				isDropTarget: boolean;
				isDragging: boolean;
				hasChild: boolean;
			}
		) => (
			<CustomNode
				node={node}
				depth={depth}
				isOpen={isOpen}
				hasChild={hasChild}
				isDropTarget={isDropTarget}
				isDragging={nodeIsDragging}
				onToggle={onToggle}
			/>
		),
		[]
	);

	const dragPreviewRender = useCallback(
		(
			monitorProps: Parameters<
				typeof CustomDragPreview
			>[0]["monitorProps"]
		) => <CustomDragPreview monitorProps={monitorProps} />,
		[]
	);

	const canDrop = useCallback(
		(
			tree: NodeModel<PostData>[],
			{
				dragSource,
				dropTargetId,
			}: {
				dragSource?: NodeModel<PostData>;
				dropTargetId: NodeModel["id"];
			}
		) => {
			if (!dragSource) {
				return false;
			}

			const dragId = Number(dragSource.id);
			const targetId = Number(dropTargetId);
			const dragParentId = Number(dragSource.parent ?? 0);

			if (dragId === targetId) {
				return false;
			}

			if (isAncestor(tree, dragId, targetId)) {
				return false;
			}

			if (!hierarchical) {
				return dragParentId === targetId;
			}

			// Reorder among siblings (dropTargetId is the shared parent, including root).
			if (dragParentId === targetId) {
				return true;
			}

			// Move to top level or nest under another page.
			return true;
		},
		[hierarchical]
	);

	const placeholderRender = useCallback(
		(node: NodeModel<PostData>, { depth }: { depth: number }) => (
			<Placeholder
				node={node}
				depth={depth}
			/>
		),
		[]
	);

	const listClassName = [
		"reorder-posts-list",
		isDragging ? reorderClasses.treeDragging : "",
	]
		.filter(Boolean)
		.join(" ");

	return (
		<div className={listClassName}>
			<DndProvider
				backend={MultiBackend}
				options={getBackendOptions()}
			>
				<Tree<PostData>
					tree={treeModels}
					rootId={0}
					sort={false}
					insertDroppableFirst={false}
					initialOpen={openIds}
					onChangeOpen={handleChangeOpen}
					onDrop={handleDrop}
					onDragStart={handleDragStart}
					onDragEnd={handleDragEnd}
					render={renderNode}
					dragPreviewRender={dragPreviewRender}
					classes={{
						root: reorderClasses.treeRoot,
						draggingSource: reorderClasses.draggingSource,
						placeholder: reorderClasses.placeholderContainer,
						dropTarget: `${reorderClasses.dropTarget} ${reorderClasses.canDrop}`,
					}}
					canDrop={canDrop}
					dropTargetOffset={10}
					placeholderRender={placeholderRender}
				/>
			</DndProvider>
			{hasMoreRoots && (
				<div className={reorderClasses.loadMoreWrap}>
					<Button
						variant="secondary"
						className={reorderClasses.loadMoreButton}
						onClick={handleLoadMore}
						disabled={isLoadingMore || isDragging}
						isBusy={isLoadingMore}
					>
						{__("Load More", "metronet-reorder-posts")}
					</Button>
				</div>
			)}
		</div>
	);
};

export default List;
