export type PostData = {
	id: number;
	title: string;
	url: string;
	menu_order: number;
	parent?: number;
	droppable?: boolean;
};
