import * as React from 'react';
import './App.css';
import { TVChartContainer } from './components/TVChartContainer/index';
import { makeStyles, withStyles } from '@material-ui/core/styles';
import InputLabel from '@material-ui/core/InputLabel';
import FormControl from '@material-ui/core/FormControl';
import NativeSelect from '@material-ui/core/NativeSelect';
import InputBase from '@material-ui/core/InputBase';
import Chip from '@material-ui/core/Chip';
import {Assessment, SubdirectoryArrowRightOutlined} from '@material-ui/icons';
const BootstrapInput = withStyles((theme) => ({
	root: {
		'label + &': {
			marginTop: theme.spacing(3),
		},
		
	},
	input: {
		borderRadius: 4,
		position: 'relative',
		backgroundColor: theme.palette.background.paper,
		border: '1px solid #ced4da',
		fontSize: 16,
		padding: '10px 26px 10px 12px',
		transition: theme.transitions.create(['border-color', 'box-shadow']),
		// Use the system font instead of the default Roboto font.
		fontFamily: [
			'-apple-system',
			'BlinkMacSystemFont',
			'"Segoe UI"',
			'Roboto',
			'"Helvetica Neue"',
			'Arial',
			'sans-serif',
			'"Apple Color Emoji"',
			'"Segoe UI Emoji"',
			'"Segoe UI Symbol"',
		].join(','),
		'&:focus': {
			borderRadius: 4,
			borderColor: '#80bdff',
			boxShadow: '0 0 0 0.2rem rgba(0,123,255,.25)',
		},
	},
}))(InputBase);

const useStyles = makeStyles((theme) => ({
	margin: {
		margin: theme.spacing(1),
	},
}));
export default function App() {
	const classes = useStyles();
	const [ticker, setTicker] = React.useState('GBP/USD');
	const [timeframe, setTimeframe] = React.useState('1440');
	const [range, setRange] = React.useState('2020');
	const [amount, setAmount] = React.useState('');
	const [currency, setCurrency] = React.useState('');
	const [trader, setTrader] = React.useState('');
	
	return (
		<div className={'App'}>
			<header className={'App-header'}>
				<h3 className={'App-title'}>
					Strategy Name
					</h3>
					&nbsp;
				<Chip
					icon={<Assessment />}
					label="Summary"
					clickable
					color="primary"
					variant="outlined"
				/>
				<FormControl className={classes.margin}>
					<InputLabel htmlFor="ticker-id">Ticker</InputLabel>
					<NativeSelect
						id="ticker-id"
						value={ticker}
						onChange={(e)=>setTicker(e.target.value)}
						input={<BootstrapInput />}
					>
						<option>GBP/USD</option>
						<option>CAD/USD</option>
					</NativeSelect>
				</FormControl>
				<FormControl className={classes.margin}>
					<InputLabel htmlFor="timeframe-id">Timeframe</InputLabel>
					<NativeSelect
						id="timeframe-id"
						value={timeframe}
						onChange={e=>setTimeframe(e.target.value)}
						input={<BootstrapInput />}
					>
						<option value={1}>1 minute</option>
						<option value={5}>5 minutes</option>
						<option value={15}>15 minutes</option>
						<option value={30}>30 minutes</option>
						<option value={60}>1 hour</option>
						<option value={240}>4 hours</option>
						<option value={1440}>1 day</option>
					</NativeSelect>
				</FormControl>
				<FormControl className={classes.margin}>
					<InputLabel htmlFor="range-id">Validation Range</InputLabel>
					<NativeSelect
						id="range-id"
						value={range}
						onChange={e=>setRange(e.target.value)}
						input={<BootstrapInput />}
					>
						<option value="2021">From 2021</option>
						<option value="2020">From 2020</option>
						<option value="2019">From 2019</option>
						<option value="2018">From 2018</option>
					</NativeSelect>
				</FormControl>
				<FormControl className={classes.margin}>
					<InputLabel htmlFor="amout-id">Invest amount</InputLabel>
					<BootstrapInput id="demo-customized-textbox" value={amount}
					 onChange={e=>setAmount(e.target.value)} />
					
				</FormControl>
				<FormControl className={classes.margin}>
					<InputLabel htmlFor="currency-id"></InputLabel>
				
					<NativeSelect
						id="currency-id"
						value={currency}
						onChange={e=>setCurrency(e.target.value)}
						input={<BootstrapInput />}
					>
						<option>USD</option>
					</NativeSelect>
				</FormControl>
				<FormControl className={classes.margin}>
					<InputLabel htmlFor="trader-id">Broker/Trader</InputLabel>
					<NativeSelect
						id="trader-id"
						value={trader}
						onChange={e=>setTrader(e.target.value)}
						input={<BootstrapInput />}
					>
						<option>None</option>
					</NativeSelect>
				</FormControl>
			</header>
			<TVChartContainer symbol={ticker} interval={timeframe} from={range}/>
		</div>
	);
}

