import React, { useCallback } from "react";
import { Icon, Spinner } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useSelect } from "@wordpress/data";
import { NodeModel, useDragOver } from "@minoru/react-dnd-treeview";
import { reorderClasses } from "./classes";
import { PostData } from "./types";
import { ensureChildrenLoaded, getBoundSelectors } from "./store";

type Props = {
	node: NodeModel<PostData>;
	depth: number;
	isOpen: boolean;
	hasChild: boolean;
	isDropTarget: boolean;
	isDragging: boolean;
	onToggle: () => void;
};

const calculateIndent = (depth: number, hasChild: boolean) => {
	let calculatedIndent = 0;
	let calculatedDepth = 0;
	calculatedDepth = depth + 1;
	if (depth === 0 && !hasChild) {
		calculatedDepth = 0;
	}
	calculatedIndent = calculatedDepth * 30;
	if (hasChild) {
		calculatedIndent -= 40;
	}
	return calculatedIndent;
};

export const CustomNode: React.FC<Props> = (props) => {
	const nodeId = Number(props.node.id);
	const showExpand = props.hasChild || Boolean(props.node.data?.has_children);

	const isChildrenLoading = useSelect(
		(selectStore) => {
			const store = getBoundSelectors(selectStore);
			return store.isNodeChildrenLoading(nodeId);
		},
		[nodeId]
	);

	const indent = calculateIndent(props.depth, showExpand);

	const handleToggle = useCallback(async () => {
		await ensureChildrenLoaded(nodeId);
		props.onToggle();
	}, [nodeId, props.onToggle]);

	const handleDragOverOpen = useCallback(
		(targetId: NodeModel["id"]) => {
			void (async () => {
				await ensureChildrenLoaded(Number(targetId));
				props.onToggle();
			})();
		},
		[props.onToggle]
	);

	const dragOverProps = useDragOver(nodeId, props.isOpen, handleDragOverOpen);

	const nodeClassName = [
		"tree-node",
		reorderClasses.node,
		props.isDropTarget ? reorderClasses.nodeIsDropTarget : "",
		props.isDragging ? reorderClasses.nodeIsDragging : "",
	]
		.filter(Boolean)
		.join(" ");

	return (
		<div
			className={nodeClassName}
			{...dragOverProps}
		>
			{showExpand && (
				<div className={reorderClasses.nodeExpandWrap}>
					<button
						type="button"
						className={`${reorderClasses.nodeExpand} ${
							props.isOpen ? reorderClasses.nodeExpandOpen : ""
						}`}
						onClick={(e) => {
							e.stopPropagation();
							void handleToggle();
						}}
						disabled={isChildrenLoading}
						aria-expanded={props.isOpen}
						aria-label={
							props.isOpen
								? __("Collapse", "metronet-reorder-posts")
								: __("Expand", "metronet-reorder-posts")
						}
					>
						{isChildrenLoading ? (
							<Spinner />
						) : (
							<Icon
								icon="arrow-right"
								size={20}
							/>
						)}
					</button>
				</div>
			)}

			<div
				className={reorderClasses.nodeContent}
				style={{ paddingInlineStart: indent }}
			>
				<div className={reorderClasses.nodeLabelWrap}>
					<span className={reorderClasses.nodeLabel}>
						{props.node.text}
					</span>
				</div>
			</div>
		</div>
	);
};
