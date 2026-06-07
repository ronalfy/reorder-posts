import React from "react";
import { Icon } from "@wordpress/components";
import { chevronRight } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import { NodeModel, useDragOver } from "@minoru/react-dnd-treeview";
import { reorderClasses } from "./classes";
import { PostData } from "./types";
import { TypeIcon } from "./TypeIcon";

type Props = {
	node: NodeModel<PostData>;
	depth: number;
	isOpen: boolean;
	hasChild: boolean;
	isDropTarget: boolean;
	isDragging: boolean;
	onToggle: (id: NodeModel["id"]) => void;
};

const calculateIndent = (depth: number, hasChild: boolean) => {
	let calculatedIndent = 0;
	let calculatedDepth = 0;
	calculatedDepth = depth + 1;
	if ( depth === 0 && ! hasChild ) {
		calculatedDepth = 0;
	}
	calculatedIndent = calculatedDepth * 30;
	if ( hasChild ) {
		calculatedIndent -= 40;
	}
	return calculatedIndent;
};

export const CustomNode: React.FC<Props> = (props) => {
	const { id, droppable } = props.node;
	const indent = calculateIndent( props.depth, props.hasChild );

	const handleToggle = (e: React.MouseEvent) => {
		e.stopPropagation();
		props.onToggle(props.node.id);
	};

	const dragOverProps = useDragOver(id, props.isOpen, props.onToggle);

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
			{props.hasChild && (
				<div className={reorderClasses.nodeExpandWrap}>
					<button
						type="button"
						className={`${reorderClasses.nodeExpand} ${
							props.isOpen ? reorderClasses.nodeExpandOpen : ""
						}`}
						onClick={handleToggle}
						aria-expanded={props.isOpen}
						aria-label={
							props.isOpen
								? __("Collapse", "metronet-reorder-posts")
								: __("Expand", "metronet-reorder-posts")
						}
					>
						<Icon
							icon="arrow-right"
							size={20}
						/>
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
