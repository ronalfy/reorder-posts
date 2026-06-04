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
	onToggle: (id: NodeModel["id"]) => void;
};

export const CustomNode: React.FC<Props> = (props) => {
	const { id, droppable } = props.node;
	const indent = props.depth * 24;

	const handleToggle = (e: React.MouseEvent) => {
		e.stopPropagation();
		props.onToggle(props.node.id);
	};

	const dragOverProps = useDragOver(id, props.isOpen, props.onToggle);


	return (
		<div
			className={`tree-node ${reorderClasses.node}`}
			style={{ paddingInlineStart: indent }}
			{...dragOverProps}
		>
			<div className={`${reorderClasses.nodeExpandWrap} ${props.isOpen ? reorderClasses.nodeExpandOpen : ""}`}>
				{props.node.droppable && (
					<div onClick={handleToggle}>
						<Icon icon={chevronRight} size={20} />
					</div>
				)}
			</div>
			<div className={reorderClasses.nodeTypeIcon}>
				<TypeIcon droppable={droppable || false} />
			</div>
			<div className={reorderClasses.nodeLabelWrap}>
				<span className={reorderClasses.nodeLabel}>{props.node.text}</span>
			</div>
		</div>
	);
};
