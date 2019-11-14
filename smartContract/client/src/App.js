import React from 'react';
import './App.css';

import CssBaseline from '@material-ui/core/CssBaseline';
import { BrowserRouter as Router, Route, Switch} from 'react-router-dom';
import { Redirect } from 'react-router';

import NewProject from './pages/newProject/newProject';
import Projects from './pages/projects/projects';

class App extends React.Component {

  render() {
    return (
      <div className="App">
        <CssBaseline/>
        <link rel="icon" href="/public/favicon.png" type="image/x-icon"/>
        <Router>
          <Switch>
            <Route path="/crear" exact component={NewProject} />
            <Route path="/proyectos" component={Projects} />
            <Redirect to="/proyectos" />
          </Switch>
        </Router>
      </div>
    );
  }
}

export default App;
